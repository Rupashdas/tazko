<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'status'      => $this->status,
            'priority'    => $this->priority,
            'due_date'    => $this->due_date?->toDateString(),
            'sort_order'  => $this->sort_order,
            'created_at'  => $this->created_at,

            'assignees' => $this->whenLoaded('assignees', fn() =>
                $this->assignees->map(fn($user) => [
                    'id'     => $user->id,
                    'name'   => $user->name,
                    'avatar' => $user->avatar ? asset('storage/' . $user->avatar) : null,
                ])
            ),

            'created_by' => $this->whenLoaded('createdBy', fn() => [
                'id'   => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ]),

            'labels' => $this->whenLoaded('labels', fn() =>
                $this->labels->map(fn($label) => [
                    'id'    => $label->id,
                    'name'  => $label->name,
                    'color' => $label->color,
                ])
            ),

            'subtasks' => $this->whenLoaded('subtasks', fn() =>
                $this->subtasks->map(fn($subtask) => [
                    'id'         => $subtask->id,
                    'title'      => $subtask->title,
                    'is_done'    => $subtask->is_done,
                    'sort_order' => $subtask->sort_order,
                ])
            ),

            'project' => $this->whenLoaded('project', fn() => [
                'id'      => $this->project->id,
                'name'    => $this->project->name,
                'members' => $this->project->relationLoaded('members')
                    ? $this->project->members->map(fn($m) => [
                        'id'     => $m->id,
                        'name'   => $m->name,
                        'avatar' => $m->avatar ? asset('storage/' . $m->avatar) : null,
                    ])
                    : [],
            ]),
        ];
    }
}
