<?php

namespace App\Concerns;

use App\Models\Attachment;
use App\Services\AttachmentSyncService;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Opt-in trait for any model whose fields contain rich-text content with
 * embedded attachment nodes (<div data-attachment-id="...">).
 *
 * Usage:
 *
 *   class Comment extends Model {
 *       use HasRteAttachments;
 *       public array $rteFields = ['body'];
 *   }
 *
 * `$rteFields` MUST be public — the sync service and boot closure read it
 * from outside the class scope, where protected properties silently
 * resolve to null.
 *
 * The trait wires model events so that on every save the sync service
 * reconciles which attachment rows belong to this parent. On delete it
 * detaches all linked attachments (which in turn removes their files
 * from disk via the Attachment model's own `deleting` event).
 *
 * Adding attachments to a new module is a three-step job:
 *   1. Add this trait to the model.
 *   2. Declare the array of RTE field names.
 *   3. Override attachmentProjectId() only if the model isn't a Project
 *      and doesn't have a project_id column.
 */
trait HasRteAttachments {

    /*---------------------------------------------------------------------------
    | Boot — wire sync + cleanup to model lifecycle
    ---------------------------------------------------------------------------*/

    protected static function bootHasRteAttachments(): void {
        static::saved(function ($model) {
            // Avoid re-parsing HTML on unrelated column updates. For fresh
            // rows we always run (nothing to compare against).
            $rteFields = $model->rteFields ?? [];
            if (! $model->wasRecentlyCreated && ! $model->wasChanged($rteFields)) {
                return;
            }
            app(AttachmentSyncService::class)->sync($model);
        });

        static::deleting(function ($model) {
            app(AttachmentSyncService::class)->detachAll($model);
        });
    }

    /*---------------------------------------------------------------------------
    | Relations + helpers consumers get for free
    ---------------------------------------------------------------------------*/

    public function attachments(): MorphMany {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Which project owns attachments created in this model's RTE content.
     * Default assumes a `project_id` column (Task, Comment). Models that
     * ARE the project itself (like Project) override this to return $this->id.
     */
    public function attachmentProjectId(): int {
        return (int) $this->project_id;
    }
}
