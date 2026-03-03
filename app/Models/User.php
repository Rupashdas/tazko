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
        'is_active',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
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
    ---------------------------------------------------------------------------*/

    public function isSuperAdmin(): bool {
        if ($this->relationLoaded('roles')) {
            return $this->roles->contains('name', 'super-admin');
        }
        return $this->roles()->where('name', 'super-admin')->exists();
    }

    public function hasCapability(string $name): bool {
        if ($this->isSuperAdmin()) return true;

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

    public function hasAnyCapability(array $names): bool {
        if ($this->isSuperAdmin()) return true;

        return $this->roles()
            ->whereHas('capabilities', fn($q) => $q->whereIn('name', $names))
            ->exists();
    }
}
