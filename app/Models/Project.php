<?php

namespace App\Models;

use App\Concerns\HasRteAttachments;
use Illuminate\Database\Eloquent\Model;

class Project extends Model {

    use HasRteAttachments;

    protected $fillable = [
        'created_by',
        'name',
        'description',
        'goal',
        'status',
        'priority',
        'color',
        'start_date',
        'end_date',
        'progress',
        'is_archived',
        'archived_at',
    ];

    protected function casts(): array {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
            'progress'    => 'integer',
        ];
    }

    // RTE fields whose attachment ids are auto-synced by HasRteAttachments.
    // Public because the trait reads it from outside class scope.
    public array $rteFields = ['description'];

    // Project IS the tenancy anchor — no `project_id` column, so override
    // the trait's default to return our own id.
    public function attachmentProjectId(): int {
        return (int) $this->id;
    }

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function labels() {
        return $this->hasMany(Label::class);
    }

    public function members() {
        return $this->belongsToMany(User::class, 'project_members')->withPivot('role')->withTimestamps();
    }

    public function comments() {
        return $this->morphMany(Comment::class, 'commentable')->orderBy('created_at');
    }

    // All attachments in the project — images in any task description,
    // any comment, or the project description itself. Powers the Files
    // tab aggregation. Distinct from `attachments()` inherited from the
    // trait, which is the morphMany for THIS project's own RTE content.
    public function allAttachments() {
        return $this->hasMany(Attachment::class)->committed();
    }
}
