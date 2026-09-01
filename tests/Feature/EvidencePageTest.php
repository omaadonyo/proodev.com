<?php

use App\Enums\EvidenceStatus;
use App\Enums\EvidenceType;
use App\Models\Evidence;
use App\Models\User;
use Livewire\Livewire;

test('the passport renders the evidence library for the owner', function () {
    $user = User::factory()->create(['public_passport' => true]);

    Livewire::actingAs($user)
        ->test('pages::devid', ['user' => $user])
        ->assertOk()
        ->assertSee('Add evidence');
});

test('adding evidence through the passport form dispatches the action', function () {
    $user = User::factory()->create(['public_passport' => true]);

    Livewire::actingAs($user)
        ->test('pages::devid', ['user' => $user])
        ->set('url', 'https://github.com/laravel/framework')
        ->call('addEvidence');

    expect($user->evidence()->where('url', 'https://github.com/laravel/framework')->exists())->toBeTrue();
});

test('the evidence show page renders a ready analysis report', function () {
    $user = User::factory()->create();

    $evidence = Evidence::create([
        'user_id' => $user->id,
        'type' => EvidenceType::GithubRepository,
        'title' => 'Inventory OS',
        'url' => 'https://github.com/acme/inventory-os',
        'source' => 'github',
        'status' => EvidenceStatus::Ready,
        'ai_score' => 70,
    ]);

    $evidence->analysis()->create([
        'summary' => 'A Laravel inventory system.',
        'technologies' => ['Laravel'],
        'engineering_areas' => ['Backend Engineering'],
        'complexity' => 'complex',
        'skills' => [['name' => 'Laravel', 'confidence' => 85]],
        'references' => [['claim' => 'Uses Laravel', 'reference' => 'README']],
    ]);

    Livewire::actingAs($user)
        ->test('pages::evidence.show', ['evidence' => $evidence])
        ->assertOk()
        ->assertSee('AI Analysis Report')
        ->assertSee('References')
        ->assertSee('Technologies Detected');
});

test('the evidence show page renders analysis summaries through safe markdown', function () {
    $user = User::factory()->create();

    $evidence = Evidence::create([
        'user_id' => $user->id,
        'type' => EvidenceType::GithubRepository,
        'title' => 'Scraped Repo',
        'url' => 'https://github.com/acme/scraped-repo',
        'source' => 'github',
        'status' => EvidenceStatus::Ready,
        'ai_score' => 70,
    ]);

    $evidence->analysis()->create([
        'summary' => "<p align=\"left\"><strong>Laravel Engineer</strong> building queues.</p>\n\n## Open Source",
        'technologies' => ['Laravel'],
        'engineering_areas' => ['Backend Engineering'],
        'complexity' => 'complex',
    ]);

    $html = Livewire::actingAs($user)
        ->test('pages::evidence.show', ['evidence' => $evidence])
        ->assertOk()
        ->html();

    expect($html)
        ->toContain('Laravel Engineer building queues')
        ->toContain('<h2>Open Source</h2>')
        ->not->toContain('<strong>Laravel Engineer</strong>')
        ->not->toContain('align=')
        ->not->toContain('&lt;p');
});

test('evidence pages are private to the owner', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();

    $evidence = Evidence::create([
        'user_id' => $owner->id,
        'type' => EvidenceType::GithubRepository,
        'title' => 'Private',
        'url' => 'https://github.com/acme/private',
        'source' => 'github',
        'status' => EvidenceStatus::Pending,
    ]);

    Livewire::actingAs($other)
        ->test('pages::evidence.show', ['evidence' => $evidence])
        ->assertStatus(404);
});

test('evidence pages are wired into the passport route', function () {
    $user = User::factory()->create(['public_passport' => true]);

    $response = $this->actingAs($user)->get(route('devid', $user->handle()));

    $response->assertOk();
});

test('the passport renders repository evidence without icon errors', function () {
    $user = User::factory()->create(['public_passport' => true]);

    foreach (['framework', 'notes', 'api'] as $i => $repo) {
        Evidence::create([
            'user_id' => $user->id,
            'type' => EvidenceType::GithubRepository,
            'title' => $repo,
            'url' => "https://github.com/acme/{$repo}",
            'source' => 'github',
            'status' => $i === 0 ? EvidenceStatus::Ready : EvidenceStatus::Pending,
            'ai_score' => $i === 0 ? 72 : null,
        ]);
    }

    $this->actingAs($user)
        ->get(route('devid', $user->handle()))
        ->assertOk()
        ->assertSee('framework')
        ->assertSee('notes');
});
