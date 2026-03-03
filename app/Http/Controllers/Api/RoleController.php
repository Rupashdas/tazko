<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RoleCollection;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller {
	public function __construct() {
		$this->middleware('capability:settings.roles.view')->only(['index', 'show']);
		$this->middleware('capability:settings.roles.manage')->only(['store', 'update', 'destroy']);
	}

	/**
	 * GET /api/roles
	 */
	public function index(): RoleCollection {
		$roles = Role::with('capabilities')->orderBy('id')->get();

		return new RoleCollection($roles);
	}

	/**
	 * GET /api/roles/{role}
	 */
	public function show(Role $role): RoleResource {
		$role->load('capabilities');

		return new RoleResource($role);
	}

	/**
	 * POST /api/roles
	 */
	public function store(Request $request): JsonResponse {
		$validated = $request->validate([
			'name'           => 'required|string|max:100|unique:roles,name|regex:/^[a-z0-9\-]+$/',
			'label'          => 'required|string|max:100',
			'capabilities'   => 'nullable|array',
			'capabilities.*' => 'integer|exists:capabilities,id',
		]);

		$role = Role::create([
			'name'           => $validated['name'],
			'label'          => $validated['label'],
			'is_system_role' => false,
		]);

		if (! empty($validated['capabilities'])) {
			$role->capabilities()->sync($validated['capabilities']);
		}

		$role->load('capabilities');

		return (new RoleResource($role))->response()->setStatusCode(201);
	}

	/**
	 * PUT /api/roles/{role}
	 */
	public function update(Request $request, Role $role): RoleResource|JsonResponse {
		if ($role->name === 'super-admin') {
			return response()->json([
				'message' => 'Super-admin capabilities are managed by the system and cannot be changed.',
			], 403);
		}

		$rules = [
			'label'          => 'required|string|max:100',
			'capabilities'   => 'nullable|array',
			'capabilities.*' => 'integer|exists:capabilities,id',
		];

		if (! $role->is_system_role) {
			$rules['name'] = "required|string|max:100|unique:roles,name,{$role->id}|regex:/^[a-z0-9\-]+$/";
		}

		$validated = $request->validate($rules);

		$updateData = ['label' => $validated['label']];

		if (! $role->is_system_role && isset($validated['name'])) {
			$updateData['name'] = $validated['name'];
		}

		$role->update($updateData);
		$role->capabilities()->sync($validated['capabilities'] ?? []);
		$role->load('capabilities');

		return new RoleResource($role);
	}

	/**
	 * DELETE /api/roles/{role}
	 */
	public function destroy(Role $role): JsonResponse {
		if ($role->is_system_role) {
			return response()->json([
				'message' => "The \"{$role->label}\" role is a system role and cannot be deleted.",
			], 403);
		}

		$role->capabilities()->detach();
		$role->users()->detach();
		$role->delete();

		return response()->json(['message' => 'Role deleted successfully.']);
	}
}
