<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource {
    public function toArray(Request $request): array {
        $authId = auth()->id();

        return [
            'id'          => $this->id,
            'body'        => $this->body,
            'is_edited'   => $this->is_edited,
            'created_at'  => $this->created_at,
            'updated_at'  => $this->updated_at,

            'user' => $this->whenLoaded('user', fn() => [
                'id'     => $this->user->id,
                'name'   => $this->user->name,
                'avatar' => $this->user->avatar ? asset('storage/' . $this->user->avatar) : null,
            ]),

            'likes_count'  => $this->when(isset($this->likes_count), $this->likes_count, fn() => $this->likes()->count()),
            'liked_by_me'  => $this->when(isset($this->liked_by_me), (bool) $this->liked_by_me, fn() => $this->likes()->where('user_id', $authId)->exists()),

            'attachments' => $this->whenLoaded('attachments', fn() =>
                $this->attachments->map(fn($a) => [
                    'id'        => $a->id,
                    'name'      => $a->name,
                    'url'       => asset('storage/' . $a->path),
                    'mime_type' => $a->mime_type,
                    'size'      => $a->size,
                ])
            ),
        ];
    }
}
