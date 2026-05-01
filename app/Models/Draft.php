<?php

namespace App\Models;

use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Draft extends Model {

    protected $fillable = [
        'user_id',
        'context_key',
        'content',
        'attachment_ids',
    ];

    protected $casts = [
        'attachment_ids' => 'array',
        'updated_at'     => 'datetime',
    ];

    public $timestamps = false;

    protected static function booted(): void {
        static::saving(function (self $draft) {
            $draft->updated_at = now();
        });
    }

    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    public function scopeActive(Builder $q): Builder {
        $ttl = (int) config('attachments.draft_ttl_days', 30);
        return $q->where('updated_at', '>=', now()->subDays($ttl));
    }

    public function syncAttachmentIds(): void {
        $this->attachment_ids = $this->extractIds($this->content ?? '')->all();
    }

    private function extractIds(?string $html): \Illuminate\Support\Collection {
        if (! $html || trim($html) === '') return collect();

        $wrapped = '<!DOCTYPE html><html><head><meta charset="utf-8"></head>'
                 . '<body>'.$html.'</body></html>';

        $doc = new DOMDocument();
        $previousInternalErrors = libxml_use_internal_errors(true);
        $doc->loadHTML($wrapped, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previousInternalErrors);

        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query('//*[@data-attachment-id]');

        $ids = collect();
        foreach ($nodes as $node) {
            $raw = $node->getAttribute('data-attachment-id');
            if (ctype_digit($raw)) {
                $ids->push((int) $raw);
            }
        }
        return $ids->unique()->values();
    }
}
