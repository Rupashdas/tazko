<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model {

    protected $fillable = [
        'uploaded_by',
        'project_id',
        'attachable_type',
        'attachable_id',
        'name',
        'path',
        'mime_type',
        'size',
    ];

    protected function casts(): array {
        return [
            'size' => 'integer',
        ];
    }

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function uploader() {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function attachable() {
        return $this->morphTo();
    }
}
