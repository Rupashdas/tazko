<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttachmentResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            // The URL the RTE embeds. Always route-based so access control
            // runs on every fetch — never a direct /storage/... URL.
            'url'           => route('attachments.stream', ['attachment' => $this->id]),
            'mime_type'     => $this->mime_type,
            'file_type'     => $this->file_type,
            'size'          => $this->size,
            'project_id'    => $this->project_id,
            'uploaded_by'   => $this->uploaded_by,
            'attachable_type' => $this->attachable_type,
            'attachable_id'   => $this->attachable_id,
            'committed_at'  => $this->committed_at,
            'created_at'    => $this->created_at,

            'uploader' => $this->whenLoaded('uploader', fn() => [
                'id'     => $this->uploader->id,
                'name'   => $this->uploader->name,
                'avatar' => $this->uploader->avatar ? asset('storage/'.$this->uploader->avatar) : null,
            ]),
        ];
    }
}
