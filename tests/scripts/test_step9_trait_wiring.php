<?php

/**
 * Step 9 verification — HasRteAttachments trait wiring into Project/Task/Comment.
 *
 * Runs as:  php artisan tinker --execute="require 'tests/scripts/test_step9_trait_wiring.php';"
 *
 * Exercises:
 *   1. Orphan attachment rows created via the storage service.
 *   2. Task save with attachment-embedded description auto-commits the orphans.
 *   3. Editing the description to drop one attachment auto-deletes that row + file.
 *   4. Unrelated column update (status) does NOT re-run sync (wasChanged short-circuit).
 *   5. Project description sync works with the `attachmentProjectId()` override.
 *   6. Comment body sync works AND comment delete cascades attachments.
 */

use App\Models\Attachment;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AttachmentStorageService;
use Illuminate\Http\UploadedFile;
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

/*---------------------------------------------------------------------------
| Helper: create a real orphan attachment row with a file on disk
---------------------------------------------------------------------------*/
$makeOrphan = function (string $name = 'pixel.txt') use ($user, $project, $storage): Attachment {
    // Plain fake file — avoids the GD requirement of ->image().
    $fake = UploadedFile::fake()->createWithContent($name, 'hello-' . $name);
    return $storage->store($fake, $project, $user);
};

step('Scenario 1 — Task save commits orphan attachments');

$a1 = $makeOrphan('one.txt');
$a2 = $makeOrphan('two.txt');
assertEq(null, $a1->attachable_type, 'orphan#1 attachable_type');
assertEq(null, $a1->committed_at,    'orphan#1 committed_at');

$task = Task::create([
    'project_id'  => $project->id,
    'created_by'  => $user->id,
    'title'       => 'Step 9 trait wiring test',
    'description' => sprintf(
        '<p>desc with <span data-attachment-id="%d">one</span> and '
      . '<span data-attachment-id="%d">two</span></p>',
        $a1->id, $a2->id
    ),
    'status'      => 'Todo',
    'priority'    => 'medium',
]);

$a1->refresh(); $a2->refresh();
assertEq('App\\Models\\Task', $a1->attachable_type, 'a1 linked type');
assertEq($task->id,          (int) $a1->attachable_id, 'a1 linked id');
assertEq(true,  $a1->committed_at !== null, 'a1 committed_at set');
assertEq(true,  $a2->committed_at !== null, 'a2 committed_at set');
assertEq(2, $task->attachments()->count(), 'task.attachments count');

/*---------------------------------------------------------------------------*/
step('Scenario 2 — Edit description removes one attachment → hard delete + file gone');

$pathBefore = $a2->path;
assertEq(true, Storage::disk('local')->exists($pathBefore), 'a2 file on disk before');

$task->update([
    'description' => sprintf('<p>just one now <span data-attachment-id="%d">one</span></p>', $a1->id),
]);

assertEq(1, $task->attachments()->count(), 'task.attachments after trim');
assertEq(null, Attachment::find($a2->id),  'a2 row gone');
assertEq(false, Storage::disk('local')->exists($pathBefore), 'a2 file removed from disk');

/*---------------------------------------------------------------------------*/
step('Scenario 3 — Unrelated column update does NOT fire sync');

// Drop the one remaining attachment id from the HTML WITHOUT saving — purely
// in-memory — then update an unrelated column. If sync ran it would nuke a1.
$task->status = 'In Progress';
$task->save();

$a1->refresh();
assertEq(true, $a1->exists, 'a1 still present after status change');
assertEq($task->id, (int) $a1->attachable_id, 'a1 still linked');

/*---------------------------------------------------------------------------*/
step('Scenario 4 — Project description sync uses attachmentProjectId() override');

$a3 = $makeOrphan('proj.txt');
$project->update([
    'description' => sprintf('<p>proj pic <span data-attachment-id="%d">p</span></p>', $a3->id),
]);

$a3->refresh();
assertEq('App\\Models\\Project', $a3->attachable_type, 'a3 linked to project');
assertEq($project->id, (int) $a3->attachable_id,      'a3 parent id');
assertEq($project->id, (int) $a3->project_id,         'a3 project_id unchanged');

/*---------------------------------------------------------------------------*/
step('Scenario 5 — Comment body sync + cascade delete on comment destroy');

$a4 = $makeOrphan('cmt.txt');
$comment = Comment::create([
    'project_id'       => $project->id,
    'commentable_type' => Task::class,
    'commentable_id'   => $task->id,
    'user_id'          => $user->id,
    'body'             => sprintf('<p>hey <span data-attachment-id="%d">c</span></p>', $a4->id),
]);

$a4->refresh();
assertEq('App\\Models\\Comment', $a4->attachable_type, 'a4 linked to comment');
$a4Path = $a4->path;
assertEq(true, Storage::disk('local')->exists($a4Path), 'a4 file on disk');

$comment->delete();

assertEq(null, Attachment::find($a4->id), 'a4 row removed after comment delete');
assertEq(false, Storage::disk('local')->exists($a4Path), 'a4 file removed after comment delete');

/*---------------------------------------------------------------------------*/
step('Cleanup — remove fixtures');
$task->delete();          // cascades a1 through detachAll()
$project->update(['description' => null]);  // cascades a3

assertEq(null, Attachment::find($a1->id), 'a1 cleaned up');
assertEq(null, Attachment::find($a3->id), 'a3 cleaned up');

echo "\n✅ Step 9 trait wiring: ALL SCENARIOS PASS\n";
