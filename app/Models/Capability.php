<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Capability extends Model {
    protected $fillable = [
        'name',
        'label',
        'module',
    ];

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function roles() {
        return $this->belongsToMany(Role::class);
    }
}
