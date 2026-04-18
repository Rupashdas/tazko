<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model {

    protected $fillable = [
        'email',
        'name',
        'role_id',
        'invited_by',
        'token',
        'accepted_at',
        'expires_at',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'expires_at'  => 'datetime',
    ];

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function invitedBy() {
        return $this->belongsTo(User::class, 'invited_by');
    }

    /*---------------------------------------------------------------------------
    | Helpers
    ---------------------------------------------------------------------------*/

    public static function generateToken(): string {
        // Loop until we find a token that isn't already used. Collisions are
        // astronomically unlikely with 64 random bytes but the uniqueness
        // check keeps us honest and matches the AttachmentShare pattern.
        do {
            $token = Str::random(64);
        } while (static::where('token', $token)->exists());

        return $token;
    }

    public function isPending(): bool {
        return is_null($this->accepted_at) && $this->expires_at->isFuture();
    }

    public function isExpired(): bool {
        return $this->expires_at->isPast() && is_null($this->accepted_at);
    }

    public function isAccepted(): bool {
        return !is_null($this->accepted_at);
    }

    public function getStatusAttribute(): string {
        if ($this->isAccepted()) return 'accepted';
        if ($this->isExpired())  return 'expired';
        return 'pending';
    }
}
