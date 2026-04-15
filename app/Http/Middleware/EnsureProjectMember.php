<?php

namespace App\Http\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectMember {
    /**
     * Deny access unless the authenticated user is a super-admin,
     * the project creator, or an explicit project member.
     *
     * Usage: new Middleware('project.member')
     */
    public function handle(Request $request, Closure $next): Response {
        $user    = $request->user();
        $project = $request->route('project');

        if (! $project instanceof Project) {
            return $next($request);
        }

        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $allowed = $project->created_by === $user->id
            || $project->members()->where('user_id', $user->id)->exists();

        if (! $allowed) {
            return response()->json([
                'message' => 'You do not have access to this project.',
            ], 403);
        }

        return $next($request);
    }
}
