<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource {
	public function toArray(Request $request): array {
		return [
			'id'             => $this->id,
			'name'           => $this->name,
			'label'          => $this->label,
			'is_system_role' => $this->is_system_role,
			'capabilities'   => $this->whenLoaded('capabilities', function () {
				return $this->capabilities->map(fn($cap) => [
					'id'     => $cap->id,
					'name'   => $cap->name,
					'label'  => $cap->label,
					'module' => $cap->module,
				]);
			}),
		];
	}
}
