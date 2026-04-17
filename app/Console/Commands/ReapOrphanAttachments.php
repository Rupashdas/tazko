<?php

namespace App\Console\Commands;

use App\Models\Attachment;
use Illuminate\Console\Command;

/**
 * Deletes attachment uploads that were never linked to a parent.
 *
 * Background:
 *   When a user picks a file in the RTE, we upload it immediately and store
 *   an orphan row (committed_at = NULL). If they navigate away without saving
 *   the containing task/comment/project, that orphan never gets linked and
 *   just sits on disk.
 *
 * Strategy:
 *   Iterate orphans older than attachments.orphan_ttl_hours and ->delete()
 *   them through Eloquent so each row's `deleting` event fires and removes
 *   the file from disk. Chunks keep memory flat for large backlogs.
 *
 * Schedule:
 *   Wired hourly in routes/console.php. Running hourly against a 24-hour TTL
 *   means files linger at most ~25 hours, and the deletion workload stays
 *   evenly spread.
 */
class ReapOrphanAttachments extends Command {

    protected $signature = 'attachments:reap-orphans
                            {--dry-run : List what would be deleted without touching anything}
                            {--chunk=200 : How many rows to process per DB round trip}';

    protected $description = 'Delete uploaded attachments that were never linked to a parent before the TTL expired.';

    public function handle(): int {
        $dryRun = (bool) $this->option('dry-run');
        $chunk  = max(1, (int) $this->option('chunk'));
        $ttl    = (int) config('attachments.orphan_ttl_hours', 24);

        $this->info("Reaping orphan attachments older than {$ttl}h" . ($dryRun ? ' [DRY RUN]' : '') . '…');

        $total     = 0;
        $filesGone = 0;
        $failed    = 0;

        // orderBy('id') + chunkById is safe under concurrent writes because
        // it walks primary key ranges forward — new orphans inserted during
        // the run just get picked up next hour.
        Attachment::orphaned()
            ->orderBy('id')
            ->chunkById($chunk, function ($batch) use (&$total, &$filesGone, &$failed, $dryRun) {
                foreach ($batch as $attachment) {
                    $total++;
                    if ($dryRun) {
                        $this->line(sprintf(
                            '  would delete #%d %s (%d bytes, uploaded %s)',
                            $attachment->id,
                            $attachment->name,
                            $attachment->size,
                            $attachment->created_at?->diffForHumans() ?? 'unknown',
                        ));
                        continue;
                    }

                    // Eloquent delete() → Attachment::deleting() → file removed
                    // from disk. One failure shouldn't kill the whole run;
                    // we log and keep going so the backlog stays under control.
                    try {
                        $attachment->delete();
                        $filesGone++;
                    } catch (\Throwable $e) {
                        $failed++;
                        report($e);
                        $this->warn("  failed to delete attachment #{$attachment->id}: {$e->getMessage()}");
                    }
                }
            });

        if ($total === 0) {
            $this->info('No orphan attachments to reap.');
            return self::SUCCESS;
        }

        $this->info($dryRun
            ? "Would reap {$total} orphan attachment(s)."
            : "Reaped {$filesGone} of {$total} orphan attachment(s)" . ($failed ? " ({$failed} failed)" : '') . '.'
        );

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
