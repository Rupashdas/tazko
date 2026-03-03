<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model {
    protected $fillable = [
        'name',
        'label',
        'is_system_role',
    ];

    protected $casts = [
        'is_system_role' => 'boolean',
    ];

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function capabilities() {
        return $this->belongsToMany(Capability::class);
    }

    public function users() {
        return $this->belongsToMany(User::class);
    }

    /*---------------------------------------------------------------------------
    | Helpers
    ---------------------------------------------------------------------------*/

    /**
     * System roles cannot be deleted or renamed.
     */
    public function isSystemRole(): bool {
        return (bool) $this->is_system_role;
    }
}
