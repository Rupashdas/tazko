<?php

namespace App\Models;

use App\Concerns\HasRteAttachments;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model {

    use HasRteAttachments;

    protected $fillable = [
        'project_id',
        'commentable_type',
        'commentable_id',
        'user_id',
        'body',
        'is_edited',
    ];

    protected function casts(): array {
        return [
            'is_edited' => 'boolean',
        ];
    }

    // RTE fields whose attachment ids are auto-synced by HasRteAttachments.
    // attachmentProjectId() is inherited (reads project_id).
    // Public because the trait reads it from outside class scope.
    public array $rteFields = ['body'];

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function commentable() {
        return $this->morphTo();
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function likes() {
        return $this->hasMany(CommentLike::class);
    }

    // attachments() is inherited from HasRteAttachments as a morphMany.
}
