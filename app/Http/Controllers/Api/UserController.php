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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller implements HasMiddleware {
    public static function middleware(): array {
        return [
            new Middleware('capability:users.view',        only: ['index']),
            new Middleware('capability:users.create',      only: ['store']),
            new Middleware('capability:users.update',      only: ['update']),
            new Middleware('capability:users.delete',      only: ['destroy']),
            new Middleware('capability:users.role.assign', only: ['assignRole']),
        ];
    }

    /**
     * GET /api/users
     */
    public function index(): JsonResponse {
        $users = User::with('roles.capabilities')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => UserResource::collection($users),
        ]);
    }

    /**
     * POST /api/users  — invite / create user
     */
    public function store(Request $request): JsonResponse {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email',
            'role_id' => 'nullable|integer|exists:roles,id',
        ]);

        $tempPassword = Str::random(12);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($tempPassword),
        ]);

        $user->preference()->create([]);

        if (!empty($validated['role_id'])) {
            $user->roles()->sync([$validated['role_id']]);
        }

        $user->load('roles.capabilities');

        // TODO: send invite email with $tempPassword

        return response()->json([
            'status'  => 'success',
            'message' => 'User invited successfully.',
            'data'    => new UserResource($user),
        ], 201);
    }

    /**
     * PUT /api/users/{user}  — update name / email
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
     * PATCH /api/users/{user}/role
     */
    public function assignRole(Request $request, User $user): JsonResponse {
        $validated = $request->validate([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        // Prevent assigning super-admin role
        $role = Role::find($validated['role_id']);
        if ($role->name === 'super-admin') {
            return response()->json([
                'status'  => 'error',
                'message' => 'Super-admin role cannot be assigned.',
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
     */
    public function destroy(User $user): JsonResponse {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Prevent deleting super-admin
        if ($user->isSuperAdmin()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Super-admin account cannot be deleted.',
            ], 403);
        }

        $user->roles()->detach();
        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User removed successfully.',
        ]);
    }
}
