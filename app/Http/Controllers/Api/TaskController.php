<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TaskController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('project.member'),
            new Middleware('project.not_archived', only: ['store', 'update', 'destroy', 'reorder']),
            new Middleware('capability:tasks.view',   only: ['index', 'show']),
            new Middleware('capability:tasks.create', only: ['store']),
            // update does granular per-field capability checks inside the controller
            // so that (e.g.) a user with only `tasks.status.update` can still hit the endpoint.
            new Middleware('capability:tasks.reorder', only: ['reorder']),
            new Middleware('capability:tasks.delete',  only: ['destroy']),
        ];
    }

    /**
     * GET /api/projects/{project}/tasks
     */
    public function index(Request $request, Project $project): JsonResponse {
        // Board views need the full list; paginate only when the caller asks.
        $query = $project->tasks()
            ->with([
                'assignees' => fn($q) => $q->with('preference')->orderBy('task_assignees.id'),
                'createdBy',
            ])
            ->orderBy('sort_order')
            ->orderBy('created_at');

        if ($request->filled('per_page')) {
            $tasks = $query->paginate((int) $request->input('per_page'));

            return response()->json([
                'data' => TaskResource::collection($tasks),
                'meta' => [
                    'current_page' => $tasks->currentPage(),
                    'last_page'    => $tasks->lastPage(),
                    'per_page'     => $tasks->perPage(),
                    'total'        => $tasks->total(),
                    'has_more'     => $tasks->hasMorePages(),
                ],
            ]);
        }

        return response()->json([
            'data' => TaskResource::collection($query->get()),
        ]);
    }

    /**
     * GET /api/projects/{project}/tasks/{task}
     */
    public function show(Project $project, Task $task): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);

        $task->load([
            'assignees' => fn($q) => $q->with('preference')->orderBy('task_assignees.id'),
            'createdBy', 'labels', 'subtasks', 'project.members.preference',
        ]);

        return response()->json(['data' => new TaskResource($task)]);
    }

    /**
     * POST /api/projects/{project}/tasks
     */
    public function store(Request $request, Project $project): JsonResponse {
        $request->validate([
            'title'          => 'required|string|max:255',
            'description'    => 'nullable|string',
            'status'         => 'nullable|in:Todo,In Progress,Review,Done',
            'priority'       => 'nullable|in:Urgent,High,Medium,Low',
            'due_date'       => 'nullable|date',
            'assignee_ids'   => 'nullable|array',
            'assignee_ids.*' => Rule::exists('users', 'id')->where('is_active', true),
        ]);

        $task = DB::transaction(function () use ($request, $project) {
            $task = $project->tasks()->create([
                'created_by'  => auth()->id(),
                'title'       => $request->title,
                'description' => $request->description,
                'status'      => $request->status ?? 'Todo',
                'priority'    => $request->priority ?? 'Medium',
                'due_date'    => $request->due_date,
                'sort_order'  => ($project->tasks()->max('sort_order') ?? 0) + 1,
            ]);

            if ($request->filled('assignee_ids')) {
                $this->autoAddMembersAndSync($project, $task, $request->assignee_ids);
            }

            return $task;
        });

        $task->load([
            'assignees' => fn($q) => $q->with('preference')->orderBy('task_assignees.id'),
            'createdBy', 'project.members.preference',
        ]);

        return response()->json(['data' => new TaskResource($task)], 201);
    }

    /**
     * POST /api/projects/{project}/tasks/reorder
     */
    public function reorder(Request $request, Project $project): JsonResponse {
        $request->validate([
            'tasks'              => 'required|array',
            'tasks.*.id'         => ['required', Rule::exists('tasks', 'id')->where('project_id', $project->id)],
            'tasks.*.sort_order' => 'required|integer',
            'tasks.*.status'     => 'sometimes|in:Todo,In Progress,Review,Done',
        ]);

        foreach ($request->tasks as $item) {
            $data = ['sort_order' => $item['sort_order']];
            if (isset($item['status'])) {
                $data['status'] = $item['status'];
            }
            $project->tasks()->where('id', $item['id'])->update($data);
        }

        return response()->json(['message' => 'Tasks reordered.']);
    }

    /**
     * PATCH /api/projects/{project}/tasks/{task}
     *
     * Granular per-field capability checks — a user only needs the capability
     * for the specific field they're changing. This lets roles like
     * `tasks.status.update` work without the broad `tasks.update`.
     */
    public function update(Request $request, Project $project, Task $task): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'title'          => 'sometimes|required|string|max:255',
            'description'    => 'sometimes|nullable|string',
            'status'         => 'sometimes|in:Todo,In Progress,Review,Done',
            'priority'       => 'sometimes|in:Urgent,High,Medium,Low',
            'due_date'       => 'sometimes|nullable|date',
            'assignee_ids'   => 'sometimes|nullable|array',
            'assignee_ids.*' => Rule::exists('users', 'id')->where('is_active', true),
            'label_ids'      => 'sometimes|nullable|array',
            'label_ids.*'    => Rule::exists('labels', 'id')->where('project_id', $project->id),
        ]);

        // Map each incoming field to the capability required to change it.
        $fieldCapabilities = [
            'title'        => 'tasks.update',
            'description'  => 'tasks.update',
            'label_ids'    => 'tasks.update',
            'status'       => 'tasks.status.update',
            'priority'     => 'tasks.priority.update',
            'due_date'     => 'tasks.deadline.update',
            'assignee_ids' => 'tasks.assign',
        ];

        $user = $request->user();
        foreach ($fieldCapabilities as $field => $capability) {
            if ($request->has($field) && !$user->hasCapability($capability)) {
                abort(403, "You do not have permission to change this task's {$field}.");
            }
        }

        $task->update($request->only(['title', 'description', 'status', 'priority', 'due_date']));

        if ($request->has('assignee_ids')) {
            DB::transaction(function () use ($request, $project, $task) {
                $this->autoAddMembersAndSync($project, $task, $request->assignee_ids ?? []);
            });
        }

        if ($request->has('label_ids')) {
            $task->labels()->sync($request->label_ids ?? []);
        }

        $task->load([
            'assignees' => fn($q) => $q->with('preference')->orderBy('task_assignees.id'),
            'createdBy', 'labels', 'subtasks', 'project.members.preference',
        ]);

        return response()->json(['data' => new TaskResource($task)]);
    }

    /**
     * Auto-add any assignees who are not yet project members, then sync task assignees.
     * Must be called inside a DB::transaction().
     */
    private function autoAddMembersAndSync(Project $project, Task $task, array $assigneeIds): void {
        if (empty($assigneeIds)) {
            $task->assignees()->sync([]);
            return;
        }

        $project->loadMissing('members');
        $existingMemberIds = $project->members->pluck('id');

        $newMemberIds = collect($assigneeIds)->diff($existingMemberIds);
        foreach ($newMemberIds as $userId) {
            $project->members()->attach($userId, ['role' => null]);
        }

        // Bust the loaded relation so the response reflects the new state
        $project->unsetRelation('members');

        $task->assignees()->sync($assigneeIds);
    }

    /**
     * DELETE /api/projects/{project}/tasks/{task}
     */
    public function destroy(Project $project, Task $task): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);

        $task->delete();

        return response()->json(['message' => 'Task deleted successfully.']);
    }
}
