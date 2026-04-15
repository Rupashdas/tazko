<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model {

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

    public function attachments() {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
