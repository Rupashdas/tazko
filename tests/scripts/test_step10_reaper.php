<?php

/**
 * Step 10 verification — ReapOrphanAttachments command.
 *
 * Creates three attachments:
 *   A) old orphan  — older than TTL, committed_at NULL  → should be reaped
 *   B) fresh orphan — seconds old, committed_at NULL    → should survive
 *   C) committed    — linked + committed_at set          → should survive
 *
 * Runs dry-run first (expects nothing deleted), then real run.
 *
 * Invoke: php artisan tinker --execute="require 'tests/scripts/test_step10_reaper.php';"
 */

use App\Models\Attachment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AttachmentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

function step(string $msg): void { echo "\n▶ {$msg}\n"; }
function ok(string $msg): void   { echo "  ✓ {$msg}\n"; }
function fail(string $msg): never { echo "  ✗ {$msg}\n"; exit(1); }
function assertEq($expected, $actual, string $label): void {
    if ($expected === $actual) { ok("{$label} = " . var_export($actual, true)); return; }
    fail("{$label}: expected " . var_export($expected, true) . ", got " . var_export($actual, true));
}

$user    = User::find(2)    ?? fail('user #2 missing');
$project = Project::find(1) ?? fail('project #1 missing');
auth()->login($user);

$storage = app(AttachmentStorageService::class);

$makeFile = fn(string $n) => UploadedFile::fake()->createWithContent($n, 'body-'.$n);

$ttlHours = (int) config('attachments.orphan_ttl_hours', 24);

step('Seed — create A (old orphan), B (fresh orphan), C (committed)');

$a = $storage->store($makeFile('old-orphan.txt'), $project, $user);
$b = $storage->store($makeFile('fresh-orphan.txt'), $project, $user);
$c = $storage->store($makeFile('committed.txt'),   $project, $user);

// Backdate A past the TTL — raw DB update bypasses model timestamps.
DB::table('attachments')
    ->where('id', $a->id)
    ->update(['created_at' => now()->subHours($ttlHours + 1)]);
$a->refresh();

// Commit C by attaching it to a task via a real save (exercises the
// same sync path real code uses, so we're sure `committed_at` gets set).
$task = Task::create([
    'project_id'  => $project->id,
    'created_by'  => $user->id,
    'title'       => 'Step 10 committed holder',
    'description' => sprintf('<p>ok <span data-attachment-id="%d">c</span></p>', $c->id),
    'status'      => 'Todo',
    'priority'    => 'medium',
]);
$c->refresh();

$aPath = $a->path;
$bPath = $b->path;
$cPath = $c->path;

assertEq(true,  Storage::disk($a->disk)->exists($aPath), 'A file on disk');
assertEq(true,  Storage::disk($b->disk)->exists($bPath), 'B file on disk');
assertEq(true,  Storage::disk($c->disk)->exists($cPath), 'C file on disk');
assertEq(null,  $a->committed_at, 'A orphan');
assertEq(null,  $b->committed_at, 'B orphan');
assertEq(true,  $c->committed_at !== null, 'C committed');

/*---------------------------------------------------------------------------*/
step('Dry run — lists A only, touches nothing');

$exit = Artisan::call('attachments:reap-orphans', ['--dry-run' => true]);
$output = Artisan::output();
echo '    ' . trim(str_replace("\n", "\n    ", $output)) . "\n";

assertEq(0, $exit, 'dry-run exit code');
assertEq(true,  str_contains($output, "#{$a->id}"), 'dry-run mentions A');
assertEq(false, str_contains($output, "#{$b->id}"), 'dry-run skips B');
assertEq(false, str_contains($output, "#{$c->id}"), 'dry-run skips C');

// Nothing actually deleted
assertEq(true, Attachment::find($a->id) !== null, 'A still present after dry run');
assertEq(true, Storage::disk($a->disk)->exists($aPath), 'A file still present after dry run');

/*---------------------------------------------------------------------------*/
step('Real run — A gone, B and C untouched');

$exit = Artisan::call('attachments:reap-orphans');
$output = Artisan::output();
echo '    ' . trim(str_replace("\n", "\n    ", $output)) . "\n";

assertEq(0, $exit, 'real-run exit code');
assertEq(null, Attachment::find($a->id),                     'A row gone');
assertEq(false, Storage::disk($a->disk)->exists($aPath),     'A file gone from disk');
assertEq(true,  Attachment::find($b->id) !== null,           'B row survives');
assertEq(true,  Storage::disk($b->disk)->exists($bPath),     'B file survives');
assertEq(true,  Attachment::find($c->id) !== null,           'C row survives');
assertEq(true,  Storage::disk($c->disk)->exists($cPath),     'C file survives');

/*---------------------------------------------------------------------------*/
step('Cleanup — remove B and the holder task (cascades C)');

$b->delete();
$task->delete();

assertEq(null, Attachment::find($b->id), 'B cleaned up');
assertEq(null, Attachment::find($c->id), 'C cleaned up');
assertEq(false, Storage::disk('local')->exists($bPath), 'B file cleaned');
assertEq(false, Storage::disk('local')->exists($cPath), 'C file cleaned');

echo "\n✅ Step 10 reaper: ALL SCENARIOS PASS\n";
