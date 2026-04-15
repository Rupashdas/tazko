<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProjectMemberController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('project.member'),
            new Middleware('capability:projects.members.manage', only: ['store', 'destroy']),
        ];
    }

    /**
     * POST /api/projects/{project}/members
     *
     * Body: { "members": [{ "user_id": 3, "role": "Tech Lead" }, ...] }
     * Returns: { "members": [...] }  — same shape as ProjectResource members
     */
    public function store(Request $request, Project $project): JsonResponse
    {
        $validated = $request->validate([
            'members'           => 'required|array|min:1',
            'members.*.user_id' => 'required|integer|exists:users,id',
            'members.*.role'    => 'nullable|string|max:100',
        ]);

        // Fetch IDs already on this project to avoid duplicate-key errors
        $existingIds = $project->members()->pluck('users.id')->all();

        foreach ($validated['members'] as $item) {
            if (in_array($item['user_id'], $existingIds)) {
                continue; // silent skip — already a member
            }
            $project->members()->attach($item['user_id'], [
                'role' => $item['role'] ?? null,
            ]);
        }

        // Reload and return the full, up-to-date member list
        $project->load('members');

        $members = $project->members->map(fn($user) => [
            'id'     => $user->id,
            'name'   => $user->name,
            'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'role'   => $user->pivot->role,
        ]);

        return response()->json(['members' => $members], 201);
    }

    /**
     * DELETE /api/projects/{project}/members/{user}
     */
    public function destroy(Project $project, User $user): JsonResponse
    {
        abort_if(
            $project->created_by === $user->id,
            403,
            'The project owner cannot be removed from the project.'
        );

        $project->members()->detach($user->id);

        return response()->json(['message' => 'Member removed successfully.']);
    }
}
