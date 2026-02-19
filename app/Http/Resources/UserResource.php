<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'avatar' => $this->avatar ? asset('storage/' . $this->avatar) : null,
            'email' => $this->email,
            'title' => $this->title,
            'phone' => $this->phone,
            'bio' => $this->bio,
            'location' => $this->location,
        ];

    }
}
