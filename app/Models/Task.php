<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model {

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
