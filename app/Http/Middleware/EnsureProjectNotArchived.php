<?php

namespace App\Http\Middleware;

use App\Models\Project;
use App\Models\Task;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectNotArchived {
    /**
     * Block write operations on archived projects (and on tasks/comments/etc.
     * that belong to an archived project).
     *
     * Usage: new Middleware('project.not_archived')
     */
    public function handle(Request $request, Closure $next): Response {
        $project = $request->route('project');

        if (! $project instanceof Project) {
            // Try resolving via a task route param — task routes share this middleware.
            $task = $request->route('task');
            if ($task instanceof Task) {
                $project = $task->project;
            }
        }

        if ($project instanceof Project && $project->is_archived) {
            return response()->json([
                'message' => 'This project is archived. Restore it before making changes.',
            ], 403);
        }

        return $next($request);
    }
}
