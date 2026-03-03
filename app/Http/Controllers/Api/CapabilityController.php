<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Capability;
use Illuminate\Http\JsonResponse;

class CapabilityController extends Controller {
	/**
	 * GET /api/capabilities
	 *
	 * Returns all capabilities grouped by module.
	 * This is the format the frontend RolesView expects:
	 *
	 * {
	 *   "settings": [ { id, name, label, module }, ... ],
	 *   "users":    [ ... ],
	 *   "projects": [ ... ],
	 *   ...
	 * }
	 */
	public function index(): JsonResponse {
		$capabilities = Capability::orderBy('module')->orderBy('name')->get();

		$grouped = $capabilities
			->groupBy('module')
			->map(fn($caps) => $caps->map(fn($cap) => [
				'id'     => $cap->id,
				'name'   => $cap->name,
				'label'  => $cap->label,
				'module' => $cap->module,
			])->values())
			->toArray();

		return response()->json($grouped);
	}
}
