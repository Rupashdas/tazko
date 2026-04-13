<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'goal'        => $this->goal,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'color'       => $this->color,
            'start_date'  => $this->start_date?->toDateString(),
            'end_date'    => $this->end_date?->toDateString(),
            'progress'    => $this->progress,
            'is_archived' => $this->is_archived,
            'created_at'  => $this->created_at,
            'task_counts' => [
                'total' => $this->tasks_total ?? 0,
                'done'  => $this->tasks_done  ?? 0,
            ],

            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id'     => $this->createdBy->id,
                'name'   => $this->createdBy->name,
                'avatar' => $this->createdBy->avatar
                    ? asset('storage/' . $this->createdBy->avatar)
                    : null,
            ]),

            'members' => $this->whenLoaded('members', fn() =>
                $this->members->map(fn($user) => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'avatar' => $user->avatar
                        ? asset('storage/' . $user->avatar)
                        : null,
                    'role'   => $user->pivot->role,
                ])
            ),
        ];
    }
}
