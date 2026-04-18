<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;

class SubtaskController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('project.member'),
            new Middleware('capability:tasks.subtask.manage'),
        ];
    }

    /**
     * POST /api/projects/{project}/tasks/{task}/subtasks
     */
    public function store(Request $request, Project $project, Task $task): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $subtask = $task->subtasks()->create([
            'title'      => $request->title,
            'is_done'    => false,
            'sort_order' => ($task->subtasks()->max('sort_order') ?? 0) + 1,
        ]);

        return response()->json([
            'data' => $this->format($subtask),
        ], 201);
    }

    /**
     * PATCH /api/projects/{project}/tasks/{task}/subtasks/{subtask}
     */
    public function update(Request $request, Project $project, Task $task, Subtask $subtask): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);
        abort_if($subtask->task_id !== $task->id, 404);

        $request->validate([
            'title'   => 'sometimes|required|string|max:255',
            'is_done' => 'sometimes|boolean',
        ]);

        $subtask->update($request->only(['title', 'is_done']));

        return response()->json([
            'data' => $this->format($subtask->fresh()),
        ]);
    }

    /**
     * DELETE /api/projects/{project}/tasks/{task}/subtasks/{subtask}
     */
    public function destroy(Project $project, Task $task, Subtask $subtask): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);
        abort_if($subtask->task_id !== $task->id, 404);

        $subtask->delete();

        return response()->json(['message' => 'Subtask deleted.']);
    }

    /**
     * POST /api/projects/{project}/tasks/{task}/subtasks/reorder
     *
     * Body: { subtasks: [{id, sort_order}, ...] }
     */
    public function reorder(Request $request, Project $project, Task $task): JsonResponse {
        abort_if($task->project_id !== $project->id, 404);

        $request->validate([
            'subtasks'              => 'required|array',
            'subtasks.*.id'         => ['required', Rule::exists('subtasks', 'id')->where('task_id', $task->id)],
            'subtasks.*.sort_order' => 'required|integer',
        ]);

        foreach ($request->subtasks as $item) {
            $task->subtasks()->where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return response()->json(['message' => 'Subtasks reordered.']);
    }

    private function format(Subtask $subtask): array {
        return [
            'id'         => $subtask->id,
            'title'      => $subtask->title,
            'is_done'    => (bool) $subtask->is_done,
            'sort_order' => $subtask->sort_order,
        ];
    }
}
