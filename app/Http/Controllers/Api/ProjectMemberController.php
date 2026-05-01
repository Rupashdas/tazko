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
            'members.*.user_id' => 'required|integer|exists:users,id,is_active,1',
            'members.*.role'    => 'nullable|string|max:100',
        ]);

        // syncWithoutDetaching is atomic at the DB level and idempotent —
        // existing rows are skipped, so two concurrent add-member requests
        // for the same user no longer collide on the unique pivot key.
        $pivotData = collect($validated['members'])
            ->keyBy('user_id')
            ->map(fn($item) => ['role' => $item['role'] ?? null])
            ->all();

        $project->members()->syncWithoutDetaching($pivotData);

        // Reload and return the full, up-to-date member list
        $project->load('members.preference');

        $members = $project->members->map(fn($user) => [
            'id'      => $user->id,
            'name'    => $user->name,
            'avatar'  => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'palette' => $user->preference?->palette ?? 'aurora',
            'role'    => $user->pivot->role,
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
