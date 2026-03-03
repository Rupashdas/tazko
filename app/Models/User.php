<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable {
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'title',
        'phone',
        'bio',
        'location',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function roles() {
        return $this->belongsToMany(Role::class);
    }

    public function preference() {
        return $this->hasOne(UserPreference::class);
    }

    /*---------------------------------------------------------------------------
    | Permission helpers
    | These are used by RequiresCapability middleware.
    ---------------------------------------------------------------------------*/

    /**
     * Returns true if the user has the super-admin role.
     * Super-admins bypass ALL capability checks.
     */
    public function isSuperAdmin(): bool {
        // Use loaded relation if available (avoids extra query)
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', 'super-admin');
        }

        return $this->roles()->where('name', 'super-admin')->exists();
    }

    /**
     * Check if the user has a specific capability via any of their roles.
     *
     * Usage: $user->hasCapability('settings.roles.manage')
     */
    public function hasCapability(string $name): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // If roles+capabilities are already loaded, use the collection (no extra query)
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains(
                fn($role) => $role->relationLoaded('capabilities')
                    ? $role->capabilities->contains('name', $name)
                    : $role->capabilities()->where('name', $name)->exists()
            );
        }

        return $this->roles()
            ->whereHas('capabilities', fn($q) => $q->where('name', $name))
            ->exists();
    }

    /**
     * Check if the user has ANY of the given capabilities.
     */
    public function hasAnyCapability(array $names): bool {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('capabilities', fn($q) => $q->whereIn('name', $names))
            ->exists();
    }
}
