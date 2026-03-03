<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'avatar'     => $this->avatar
                ? asset('storage/' . $this->avatar)
                : null,
            'title'      => $this->title,
            'phone'      => $this->phone,
            'bio'        => $this->bio,
            'location'   => $this->location,

            /*
             * Roles with their capabilities — needed by the frontend
             * for hasCapability() checks in useAuthStore.
             *
             * Structure: roles[].capabilities[].name
             */
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->map(fn($role) => [
                    'id'           => $role->id,
                    'name'         => $role->name,
                    'label'        => $role->label,
                    'capabilities' => $role->capabilities->map(fn($cap) => [
                        'id'    => $cap->id,
                        'name'  => $cap->name,
                        'label' => $cap->label,
                    ]),
                ]);
            }),
        ];
    }
}
