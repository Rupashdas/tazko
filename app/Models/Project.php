<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model {

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

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks() {
        return $this->hasMany(Task::class);
    }

    public function members() {
        return $this->belongsToMany(User::class, 'project_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function comments() {
        return $this->morphMany(Comment::class, 'commentable')
            ->orderBy('created_at');
    }

    public function attachments() {
        return $this->hasMany(Attachment::class);
    }
}
