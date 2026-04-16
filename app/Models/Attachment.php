<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model {

    protected $fillable = [
        'uploaded_by',
        'project_id',
        'attachable_type',
        'attachable_id',
        'name',
        'path',
        'disk',
        'mime_type',
        'file_type',
        'size',
        'committed_at',
    ];

    protected function casts(): array {
        return [
            'size'         => 'integer',
            'committed_at' => 'datetime',
        ];
    }

    /*---------------------------------------------------------------------------
    | Boot — model lifecycle hooks
    ---------------------------------------------------------------------------*/

    protected static function booted(): void {
        // Single point where bytes leave the system. Fires for direct delete,
        // cascade delete (via AttachmentSyncService::sync when a node is
        // removed from RTE content), the reaper, and model-triggered parent
        // cleanup. Cascading at the DB level alone would orphan files on
        // disk, so we do the delete through Eloquent to guarantee this runs.
        static::deleting(function (Attachment $attachment) {
            if ($attachment->path && Storage::disk($attachment->disk)->exists($attachment->path)) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }
        });
    }

    /*---------------------------------------------------------------------------
    | Relationships
    ---------------------------------------------------------------------------*/

    public function uploader(): BelongsTo {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function project(): BelongsTo {
        return $this->belongsTo(Project::class);
    }

    public function attachable(): MorphTo {
        return $this->morphTo();
    }

    public function shares(): HasMany {
        return $this->hasMany(AttachmentShare::class);
    }

    /*---------------------------------------------------------------------------
    | Scopes
    ---------------------------------------------------------------------------*/

    // Rows linked to a saved parent. Files tab queries use this.
    public function scopeCommitted(Builder $query): Builder {
        return $query->whereNotNull('committed_at');
    }

    // Orphan uploads older than the configured TTL — reaper target.
    public function scopeOrphaned(Builder $query): Builder {
        $ttlHours = (int) config('attachments.orphan_ttl_hours', 24);
        return $query->whereNull('committed_at')
                     ->where('created_at', '<', now()->subHours($ttlHours));
    }
}
