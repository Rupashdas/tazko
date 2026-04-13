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
    ];

    protected function casts(): array {
        return [
            'start_date'  => 'date',
            'end_date'    => 'date',
            'is_archived' => 'boolean',
            'progress'    => 'integer',
        ];
    }

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members() {
        return $this->belongsToMany(User::class, 'project_members')
                    ->withPivot('role')
                    ->withTimestamps();
    }
}
