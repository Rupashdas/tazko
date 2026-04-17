<?php

namespace App\Services;

use App\Models\Attachment;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Owns the HTML-side of the attachment system: extracting attachment ids
 * from rich-text content and reconciling them with parent linkage in the
 * database. Does NOT know about disks or streaming — that's
 * AttachmentStorageService's job.
 *
 * Entry points are triggered by the HasRteAttachments trait on every
 * saved / deleting event of a parent model (Project, Task, Comment, …).
 */
class AttachmentSyncService {

    /**
     * Reconcile attachments linked to this parent against what's actually
     * referenced in its RTE fields. Links newly-referenced orphans, hard-
     * deletes previously-linked attachments that were removed from the
     * content. Idempotent.
     */
    public function sync(Model $parent): void {
        $rteFields   = $parent->rteFields ?? [];
        $referenced  = $this->extractIdsFromFields($parent, $rteFields);
        $currentlyLinked = $parent->attachments()->pluck('id');

        $toLink   = $referenced->diff($currentlyLinked);
        $toUnlink = $currentlyLinked->diff($referenced);

        if ($toLink->isEmpty() && $toUnlink->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($parent, $toLink, $toUnlink) {
            if ($toLink->isNotEmpty()) {
                $this->linkOrphans($parent, $toLink);
            }
            if ($toUnlink->isNotEmpty()) {
                $this->deleteRemoved($toUnlink);
            }
        });
    }

    /**
     * Remove every attachment linked to this parent. Iterates through
     * Eloquent so each row's `deleting` event fires → file gone from disk.
     * Plain DB cascade at the FK level would orphan files on the disk.
     */
    public function detachAll(Model $parent): void {
        $parent->attachments()->get()->each->delete();
    }

    /*---------------------------------------------------------------------------
    | Internals
    ---------------------------------------------------------------------------*/

    private function extractIdsFromFields(Model $parent, array $fields): Collection {
        return collect($fields)
            ->flatMap(fn ($field) => $this->extractIds($parent->{$field} ?? ''))
            ->unique()
            ->values();
    }

    /**
     * Parse an HTML blob and return the numeric ids declared via
     * data-attachment-id. Uses DOMDocument so we don't false-match attrs
     * that appear inside comments, code blocks, or plain-text contexts.
     */
    private function extractIds(?string $html): Collection {
        if (! $html || trim($html) === '') return collect();

        // Wrap in a UTF-8 meta to keep DOMDocument from mangling non-ASCII.
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

    /**
     * Turn pre-commit orphans into committed children of $parent.
     *
     * Guards against two abuse scenarios:
     *   1. Attachment belongs to a different project (cross-project leak).
     *   2. Attachment is already linked to a DIFFERENT parent (theft /
     *      duplication). Re-linking to the same parent is allowed and
     *      handled as a no-op elsewhere.
     */
    private function linkOrphans(Model $parent, Collection $ids): void {
        $attachments = Attachment::whereIn('id', $ids)->get();

        // Ids that don't exist at all → silently drop. A stale reference
        // in the HTML (e.g. content copied from elsewhere) shouldn't break
        // the save; it just won't render.
        $projectId = $parent->attachmentProjectId();
        foreach ($attachments as $att) {
            if ((int) $att->project_id !== $projectId) {
                throw ValidationException::withMessages([
                    'attachments' => "Attachment {$att->id} does not belong to this project.",
                ]);
            }

            $isOrphan    = $att->attachable_type === null && $att->attachable_id === null;
            $alreadyOurs = $att->attachable_type === $parent->getMorphClass()
                        && (int) $att->attachable_id === (int) $parent->getKey();

            if (! $isOrphan && ! $alreadyOurs) {
                throw ValidationException::withMessages([
                    'attachments' => "Attachment {$att->id} is already linked elsewhere.",
                ]);
            }
        }

        // Single UPDATE commits the whole batch atomically.
        Attachment::whereIn('id', $attachments->pluck('id'))->update([
            'attachable_type' => $parent->getMorphClass(),
            'attachable_id'   => $parent->getKey(),
            'committed_at'    => now(),
        ]);
    }

    private function deleteRemoved(Collection $ids): void {
        // Iterate through Eloquent so each row's `deleting` model event
        // runs and its file is removed from disk.
        Attachment::whereIn('id', $ids)->get()->each->delete();
    }
}
