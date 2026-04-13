<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subtask extends Model {

    protected $fillable = ['task_id', 'title', 'is_done', 'sort_order'];

    protected function casts(): array {
        return [
            'is_done'    => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function task() {
        return $this->belongsTo(Task::class);
    }
}
