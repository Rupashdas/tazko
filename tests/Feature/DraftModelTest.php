<?php

use App\Models\Draft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists context_key, content, and attachment_ids', function () {
    $user = User::factory()->create();

    $draft = Draft::create([
        'user_id'        => $user->id,
        'context_key'    => 'task:1:comment:new',
        'content'        => '<p>hi</p>',
        'attachment_ids' => [10, 20],
    ]);

    $fresh = Draft::find($draft->id);
    expect($fresh->context_key)->toBe('task:1:comment:new');
    expect($fresh->content)->toBe('<p>hi</p>');
    expect($fresh->attachment_ids)->toBe([10, 20]);
});

it('extracts attachment ids from html via syncAttachmentIds', function () {
    $user = User::factory()->create();

    $html = '<p>Hello</p>'
          . '<div data-attachment-id="42">file.png</div>'
          . '<div data-attachment-id="99">other.pdf</div>'
          . '<div data-attachment-id="42">duplicate</div>';

    $draft = new Draft([
        'user_id'     => $user->id,
        'context_key' => 'task:1:comment:new',
        'content'     => $html,
    ]);
    $draft->syncAttachmentIds();
    $draft->save();

    expect($draft->attachment_ids)->toEqualCanonicalizing([42, 99]);
});

it('scopeActive excludes drafts older than draft_ttl_days', function () {
    config(['attachments.draft_ttl_days' => 30]);
    $user = User::factory()->create();

    $fresh = Draft::create([
        'user_id'     => $user->id,
        'context_key' => 'task:1:comment:new',
        'content'     => '<p>recent</p>',
    ]);

    $stale = Draft::create([
        'user_id'     => $user->id,
        'context_key' => 'task:2:comment:new',
        'content'     => '<p>old</p>',
    ]);
    Draft::where('id', $stale->id)->update(['updated_at' => now()->subDays(31)]);

    $active = Draft::active()->pluck('id')->all();
    expect($active)->toContain($fresh->id);
    expect($active)->not->toContain($stale->id);
});
