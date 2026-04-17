<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentShareResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'               => $this->id,
            'attachment_id'    => $this->attachment_id,
            'token'            => $this->token,
            // Absolute URL the user will paste into emails, chat, etc.
            // Routed to PublicShareController, no auth required.
            'public_url'       => route('share.show', ['token' => $this->token]),
            'enabled'          => $this->enabled,
            'allow_download'   => $this->allow_download,
            'expires_at'       => $this->expires_at,
            'is_active'        => $this->isActive(),
            'access_count'     => $this->access_count,
            'last_accessed_at' => $this->last_accessed_at,
            'created_at'       => $this->created_at,
            'updated_at'       => $this->updated_at,

            'creator' => $this->whenLoaded('creator', fn() => [
                'id'     => $this->creator->id,
                'name'   => $this->creator->name,
                'avatar' => $this->creator->avatar ? asset('storage/'.$this->creator->avatar) : null,
            ]),
        ];
    }
}
