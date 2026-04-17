<?php

namespace App\Models;

use App\Concerns\HasRteAttachments;
use Illuminate\Database\Eloquent\Model;

class Task extends Model {

    use HasRteAttachments;

    protected $fillable = [
        'project_id',
        'created_by',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'sort_order',
    ];

    protected function casts(): array {
        return [
            'due_date'   => 'date',
            'sort_order' => 'integer',
        ];
    }

    // RTE fields whose attachment ids are auto-synced by HasRteAttachments.
    // attachmentProjectId() is inherited from the trait (reads project_id).
    // Public because the trait reads it from outside class scope.
    public array $rteFields = ['description'];

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function project() {
        return $this->belongsTo(Project::class);
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignees() {
        return $this->belongsToMany(User::class, 'task_assignees')
                    ->withTimestamps();
    }

    public function labels() {
        return $this->belongsToMany(Label::class, 'task_labels')
                    ->withTimestamps();
    }

    public function subtasks() {
        return $this->hasMany(Subtask::class)->orderBy('sort_order');
    }
}
