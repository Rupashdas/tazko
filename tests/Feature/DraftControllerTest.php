<?php

use App\Models\Draft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);


it('requires auth', function () {
    $this->putJson('/api/drafts/' . urlencode('task:1:comment:new'), ['content' => '<p>hi</p>'])
         ->assertStatus(401);
});

it('upserts a draft on PUT', function () {
    $user = User::factory()->create();
    $key  = 'task:1:comment:new';

    Sanctum::actingAs($user);
    $this->putJson('/api/drafts/' . urlencode($key), ['content' => '<p>first</p>'])
         ->assertNoContent();

    expect(Draft::count())->toBe(1);
    expect(Draft::first()->content)->toBe('<p>first</p>');

    Sanctum::actingAs($user);
    $this->putJson('/api/drafts/' . urlencode($key), ['content' => '<p>second</p>'])
         ->assertNoContent();

    expect(Draft::count())->toBe(1);
    expect(Draft::first()->content)->toBe('<p>second</p>');
});

it('parses attachment ids on PUT', function () {
    $user = User::factory()->create();
    $key  = 'task:1:comment:new';

    $html = '<p>x</p><div data-attachment-id="7">a</div><div data-attachment-id="9">b</div>';
    Sanctum::actingAs($user);
    $this->putJson('/api/drafts/' . urlencode($key), ['content' => $html])
         ->assertNoContent();

    expect(Draft::first()->attachment_ids)->toEqualCanonicalizing([7, 9]);
});

it('returns the draft on GET', function () {
    $user = User::factory()->create();
    Draft::create([
        'user_id'     => $user->id,
        'context_key' => 'task:1:comment:new',
        'content'     => '<p>saved</p>',
    ]);

    Sanctum::actingAs($user);
    $this->getJson('/api/drafts/' . urlencode('task:1:comment:new'))
         ->assertOk()
         ->assertJson([
             'content' => '<p>saved</p>',
         ]);
});

it('returns 404 when no draft exists', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $this->getJson('/api/drafts/' . urlencode('task:999:comment:new'))
         ->assertNotFound();
});

it('deletes a draft on DELETE', function () {
    $user = User::factory()->create();
    Draft::create([
        'user_id'     => $user->id,
        'context_key' => 'task:1:comment:new',
        'content'     => '<p>x</p>',
    ]);

    Sanctum::actingAs($user);
    $this->deleteJson('/api/drafts/' . urlencode('task:1:comment:new'))
         ->assertNoContent();

    expect(Draft::count())->toBe(0);
});

it('returns 204 on DELETE even if draft does not exist', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $this->deleteJson('/api/drafts/' . urlencode('task:999:comment:new'))
         ->assertNoContent();
});

it('isolates drafts per user', function () {
    $alice = User::factory()->create();
    $bob   = User::factory()->create();

    Draft::create([
        'user_id'     => $alice->id,
        'context_key' => 'task:1:comment:new',
        'content'     => '<p>alice</p>',
    ]);

    Sanctum::actingAs($bob);
    $this->getJson('/api/drafts/' . urlencode('task:1:comment:new'))
         ->assertNotFound();

    Sanctum::actingAs($bob);
    $this->deleteJson('/api/drafts/' . urlencode('task:1:comment:new'))
         ->assertNoContent();

    expect(Draft::count())->toBe(1);
});

it('rejects malformed context_key', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user);
    $this->putJson('/api/drafts/' . urlencode('not-a-valid-key'), ['content' => '<p>x</p>'])
         ->assertStatus(422);
});

it('rejects oversized content', function () {
    $user = User::factory()->create();
    $huge = str_repeat('a', 2_100_000);

    Sanctum::actingAs($user);
    $this->putJson('/api/drafts/' . urlencode('task:1:comment:new'), ['content' => $huge])
         ->assertStatus(422);
});
