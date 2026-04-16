<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AttachmentShare extends Model {

    protected $fillable = [
        'attachment_id',
        'token',
        'created_by',
        'enabled',
        'allow_download',
        'expires_at',
        'access_count',
        'last_accessed_at',
    ];

    protected function casts(): array {
        return [
            'enabled'          => 'boolean',
            'allow_download'   => 'boolean',
            'expires_at'       => 'datetime',
            'last_accessed_at' => 'datetime',
            'access_count'     => 'integer',
        ];
    }

    /*---------------------------------------------------------------------------
    | Boot — auto-generate token if not explicitly provided
    ---------------------------------------------------------------------------*/

    protected static function booted(): void {
        static::creating(function (AttachmentShare $share) {
            if (empty($share->token)) {
                // 48 url-safe chars ≈ 286 bits of entropy. The DB's unique
                // index is the ultimate guard — a loop handles the (extremely
                // unlikely) collision rather than relying on probability.
                do {
                    $candidate = Str::random(48);
                } while (static::where('token', $candidate)->exists());
                $share->token = $candidate;
            }
        });
    }

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function attachment(): BelongsTo {
        return $this->belongsTo(Attachment::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*---------------------------------------------------------------------------
    | Helpers
    ---------------------------------------------------------------------------*/

    // Centralizes the "is this link usable right now?" decision so the
    // public controller, the Share UI, and policy checks all agree.
    public function isActive(): bool {
        if (! $this->enabled) return false;
        if ($this->expires_at !== null && $this->expires_at->isPast()) return false;
        return true;
    }
}
