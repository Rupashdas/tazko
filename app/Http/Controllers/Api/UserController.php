<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('capability:users.view',         only: ['index']),
            new Middleware('capability:users.profile.view', only: ['show']),
            new Middleware('capability:users.create',       only: ['store']),
            new Middleware('capability:users.update',       only: ['update']),
            new Middleware('capability:users.delete',       only: ['destroy']),
            new Middleware('capability:users.activate',     only: ['toggleActive']),
            new Middleware('capability:users.role.assign',  only: ['assignRole']),
        ];
    }

    /**
     * GET /api/users
     */
    public function index(Request $request): JsonResponse {
        $perPage = (int) $request->input('per_page', 20);
        $users = User::with('roles.capabilities')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'data' => UserResource::collection($users),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
                'per_page'     => $users->perPage(),
                'total'        => $users->total(),
                'has_more'     => $users->hasMorePages(),
            ],
        ]);
    }

    /**
     * GET /api/users/{user}
     * Requires: users.profile.view
     */
    public function show(User $user): JsonResponse {
        $user->load('roles.capabilities');

        return response()->json([
            'data' => new UserResource($user),
        ]);
    }

    /**
     * POST /api/users
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email',
            'role_id' => 'nullable|integer|exists:roles,id',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($tempPassword),
            'is_active' => true,
        ]);

        $user->preference()->create([]);

        if (!empty($validated['role_id'])) {
            $user->roles()->sync([$validated['role_id']]);
        }

        $user->load('roles.capabilities');

        return response()->json([
            'status'  => 'success',
            'message' => 'User invited successfully.',
            'data'    => new UserResource($user),
        ], 201);
    }

    /**
     * PUT /api/users/{user}
     * Requires: users.update
     */
    public function update(Request $request, User $user): JsonResponse {
        $validated = $request->validate([
            'name'  => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:users,email,{$user->id}",
        ]);

        $user->update($validated);
        $user->load('roles.capabilities');

        return response()->json([
            'status'  => 'success',
            'message' => 'User updated successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * PATCH /api/users/{user}/active
     * Requires: users.activate
     */
    public function toggleActive(User $user): JsonResponse {
        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot deactivate your own account.',
            ], 403);
        }

        // Prevent deactivating super-admin
        if ($user->isSuperAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Super-admin account cannot be deactivated.',
            ], 403);
        }

        $user->update(['is_active' => !$user->is_active]);
        $user->load('roles.capabilities');

        $status = $user->is_active ? 'activated' : 'deactivated';

        return response()->json([
            'status'  => 'success',
            'message' => "User {$status} successfully.",
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * PATCH /api/users/{user}/role
     * Requires: users.role.assign
     */
    public function assignRole(Request $request, User $user): JsonResponse {
        
        if ($user->isSuperAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Super-admin role cannot be changed.',
            ], 403);
        }

        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        $targetRole = Role::findOrFail($validated['role_id']);
        if ($targetRole->name === 'super-admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'The super-admin role cannot be assigned to any user.',
            ], 403);
        }

        $user->roles()->sync([$validated['role_id']]);
        $user->load('roles.capabilities');

        return response()->json([
            'status'  => 'success',
            'message' => 'Role assigned successfully.',
            'data'    => new UserResource($user),
        ]);
    }

    /**
     * DELETE /api/users/{user}
     * Requires: users.delete
     */
    public function destroy(User $user): JsonResponse {
        if ($user->id === auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        if ($user->isSuperAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Super-admin account cannot be deleted.',
            ], 403);
        }

        // Clean up associated data to avoid orphaned rows with dangling FKs.
        DB::transaction(function () use ($user) {
            $user->roles()->detach();
            $user->preference()?->delete();

            // Detach the user from task assignments (pivot rows).
            DB::table('task_assignees')->where('user_id', $user->id)->delete();

            // Detach from project memberships.
            DB::table('project_members')->where('user_id', $user->id)->delete();

            $user->delete();
        });

        return response()->json([
            'status'  => 'success',
            'message' => 'User removed successfully.',
        ]);
    }
}
