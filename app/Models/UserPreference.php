<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'palette',
        'appearance',
        'timezone',
        'week_start',
        'time_format',
    ];
}
