<?php

use App\Models\Attachment;
use App\Models\Draft;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    config(['attachments.orphan_ttl_hours' => 24]);
    config(['attachments.draft_ttl_days'  => 30]);
});

function makeProject(User $user): Project {
    return Project::create([
        'created_by' => $user->id,
        'name'       => 'Test Project',
        'status'     => 'Planning',
    ]);
}

function makeOrphan(User $user, Project $project, int $hoursAgo): Attachment {
    $att = Attachment::create([
        'uploaded_by'     => $user->id,
        'project_id'      => $project->id,
        'attachable_type' => null,
        'attachable_id'   => null,
        'name'            => 'x.png',
        'path'            => 'attachments/2026/04/' . uniqid() . '.png',
        'disk'            => 'local',
        'mime_type'       => 'image/png',
        'file_type'       => 'image',
        'size'            => 100,
        'committed_at'    => null,
    ]);
    Attachment::where('id', $att->id)->update([
        'created_at' => now()->subHours($hoursAgo),
        'updated_at' => now()->subHours($hoursAgo),
    ]);
    return $att->fresh();
}

it('reaps orphans not pinned to any draft', function () {
    $user    = User::factory()->create();
    $project = makeProject($user);
    $orphan  = makeOrphan($user, $project, 25);

    $this->artisan('attachments:reap-orphans')->assertSuccessful();

    expect(Attachment::find($orphan->id))->toBeNull();
});

it('skips orphans pinned to an active draft', function () {
    $user    = User::factory()->create();
    $project = makeProject($user);
    $orphan  = makeOrphan($user, $project, 25);

    Draft::create([
        'user_id'        => $user->id,
        'context_key'    => 'task:1:comment:new',
        'content'        => '<div data-attachment-id="' . $orphan->id . '">x</div>',
        'attachment_ids' => [$orphan->id],
    ]);

    $this->artisan('attachments:reap-orphans')->assertSuccessful();

    expect(Attachment::find($orphan->id))->not->toBeNull();
});

it('reaps orphans pinned only to expired drafts', function () {
    $user    = User::factory()->create();
    $project = makeProject($user);
    $orphan  = makeOrphan($user, $project, 25);

    $draft = Draft::create([
        'user_id'        => $user->id,
        'context_key'    => 'task:1:comment:new',
        'content'        => '<div data-attachment-id="' . $orphan->id . '">x</div>',
        'attachment_ids' => [$orphan->id],
    ]);
    Draft::where('id', $draft->id)->update(['updated_at' => now()->subDays(31)]);

    $this->artisan('attachments:reap-orphans')->assertSuccessful();

    expect(Attachment::find($orphan->id))->toBeNull();
});
