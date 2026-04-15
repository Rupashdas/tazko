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
use Illuminate\Validation\Rule;

class TaskController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('project.member'),
            new Middleware('capability:tasks.view',   only: ['index']),
            new Middleware('capability:tasks.create', only: ['store']),
            new Middleware('capability:tasks.update', only: ['update', 'reorder']),
            new Middleware('capability:tasks.delete', only: ['destroy']),
        ];
    }

    /**
     * GET /api/projects/{project}/tasks
     */
    public function index(Project $project): JsonResponse {
        $tasks = $project->tasks()
            ->with(['assignees', 'createdBy'])
            ->orderBy('sort_order')
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
        ]);
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
            'assignee_ids.*' => Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
        ]);

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
            $task->assignees()->sync($request->assignee_ids);
        }

        $task->load(['assignees', 'createdBy']);

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
            'assignee_ids.*' => Rule::exists('project_members', 'user_id')->where('project_id', $project->id),
        ]);

        $task->update($request->only(['title', 'description', 'status', 'priority', 'due_date']));

        if ($request->has('assignee_ids')) {
            $task->assignees()->sync($request->assignee_ids ?? []);
        }

        $task->load(['assignees', 'createdBy']);

        return response()->json(['data' => new TaskResource($task)]);
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
