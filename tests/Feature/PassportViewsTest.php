<?php

use App\Models\PassportView;
use App\Models\Project;
use App\Models\Skill;
use App\Models\User;
use App\Models\WeeklyReport;
use App\Services\PassportViewService;
use Livewire\Livewire;
use Wirechat\Wirechat\Models\Conversation;

test('viewing a public passport records a view', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();

    $this->actingAs($viewer)->get(route('passport', $owner->handle()));

    expect(PassportView::where('passport_owner_id', $owner->id)->count())->toBe(1)
        ->and(PassportView::where('viewer_id', $viewer->id)->exists())->toBeTrue();
});

test('viewing your own passport does not record a view', function () {
    $user = User::factory()->create(['public_passport' => true]);

    $this->actingAs($user)->get(route('passport', $user->handle()));

    expect(PassportView::where('passport_owner_id', $user->id)->count())->toBe(0);
});

test('the same viewer only counts once per day', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();
    $service = app(PassportViewService::class);

    expect($service->record($owner, $viewer))->toBeTrue()
        ->and($service->record($owner, $viewer))->toBeFalse()
        ->and($service->record($owner, $viewer))->toBeFalse();

    expect(PassportView::where('passport_owner_id', $owner->id)->count())->toBe(1);
});

test('a guest view is recorded with an ip address', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $service = app(PassportViewService::class);

    expect($service->record($owner, null, '127.0.0.1'))->toBeTrue();

    $view = PassportView::where('passport_owner_id', $owner->id)->first();

    expect($view)->not->toBeNull()
        ->and($view->viewer_id)->toBeNull()
        ->and($view->ip_address)->toBe('127.0.0.1');
});

test('the passport links out to the developer socials and website', function () {
    $owner = User::factory()->create([
        'public_passport' => true,
        'github_url' => 'https://github.com/mia-chen',
        'linkedin_url' => 'https://linkedin.com/in/mia-chen',
        'website_url' => 'https://mia.dev',
    ]);

    $this->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertSee('GitHub')
        ->assertSee('https://github.com/mia-chen')
        ->assertSee('LinkedIn')
        ->assertSee('https://linkedin.com/in/mia-chen')
        ->assertSee('Website')
        ->assertSee('https://mia.dev');
});

test('the passport hides socials when none are set', function () {
    $owner = User::factory()->create(['public_passport' => true]);

    $this->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertDontSee('https://github.com');
});

test('verified users see the connect button on a passport', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    Livewire::actingAs($viewer)
        ->test('pages::passport', ['user' => $owner])
        ->assertOk()
        ->assertSee('Connect');
});

test('unverified users do not see the connect button', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();

    Livewire::actingAs($viewer)
        ->test('pages::passport', ['user' => $owner])
        ->assertOk()
        ->assertDontSee('Connect');
});

test('connecting creates a private conversation and redirects to the chat', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    Livewire::actingAs($viewer)
        ->test('pages::passport', ['user' => $owner])
        ->call('connect')
        ->assertRedirect(route('wirechat.chats.chat', Conversation::firstOrFail()));

    expect(Conversation::count())->toBe(1)
        ->and($viewer->conversations()->count())->toBe(1)
        ->and($owner->conversations()->count())->toBe(1);
});

test('the passport tolerates weekly reports with legacy data keys', function () {
    $owner = User::factory()->create(['public_passport' => true]);

    WeeklyReport::create([
        'user_id' => $owner->id,
        'week_started' => now()->startOfWeek(),
        'data' => ['xp_earned' => 80, 'projects_published' => 0, 'days_active' => 7],
    ]);

    $this->actingAs($owner)
        ->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertSee('Latest Weekly Report')
        ->assertSee('XP gained');
});

test('users without an uploaded photo get a database-derived initials avatar', function () {
    $user = User::factory()->create(['name' => 'Jane Doe']);

    $url = $user->avatarUrl();

    expect($url)->toStartWith('data:image/svg+xml;base64,')
        ->and(base64_decode(substr($url, strlen('data:image/svg+xml;base64,'))))->toContain('JD')
        ->and($user->initialsAvatar())->toBe($url);

    $uploaded = User::factory()->create(['avatar_path' => 'avatars/photo.png']);

    expect($uploaded->avatarUrl())->toContain('storage/avatars/photo.png');
});

test('connecting twice reuses the same private conversation', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    Livewire::actingAs($viewer)
        ->test('pages::passport', ['user' => $owner])
        ->call('connect');

    Livewire::actingAs($viewer)
        ->test('pages::passport', ['user' => $owner])
        ->call('connect');

    expect(Conversation::count())->toBe(1);
});

test('verified users see a connect button in the passport flyout', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    Livewire::withoutLazyLoading()
        ->actingAs($viewer)
        ->test('passport-flyout-body', ['userId' => $owner->id])
        ->assertSee('Connect');
});

test('unverified users do not see a connect button in the passport flyout', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();

    Livewire::withoutLazyLoading()
        ->actingAs($viewer)
        ->test('passport-flyout-body', ['userId' => $owner->id])
        ->assertDontSee('Connect');
});

test('connecting from the flyout creates a conversation and redirects to the chat', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    Livewire::actingAs($viewer)
        ->test('passport-flyout-body', ['userId' => $owner->id])
        ->call('connect')
        ->assertRedirect(route('wirechat.chats.chat', Conversation::firstOrFail()));

    expect(Conversation::count())->toBe(1)
        ->and($viewer->conversations()->count())->toBe(1)
        ->and($owner->conversations()->count())->toBe(1);
});

test('private passports do not record views', function () {
    $owner = User::factory()->create(['public_passport' => false]);
    $viewer = User::factory()->create();
    $service = app(PassportViewService::class);

    expect($service->record($owner, $viewer))->toBeFalse();

    expect(PassportView::count())->toBe(0);
});

test('the view count and recent viewers reflect recorded views', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewerA = User::factory()->create();
    $viewerB = User::factory()->create();
    $service = app(PassportViewService::class);

    $service->record($owner, $viewerA);
    $service->record($owner, $viewerB);
    $service->record($owner, null, '127.0.0.1');

    expect($service->count($owner))->toBe(3)
        ->and($service->anonymousCount($owner))->toBe(1);

    $viewers = $service->recentViewers($owner);

    expect($viewers)->toHaveCount(2)
        ->and($viewers->pluck('viewer_id'))->toContain($viewerA->id, $viewerB->id);
});

test('the feed right panel no longer shows the passport views card', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();
    $service = app(PassportViewService::class);
    $service->record($owner, $viewer);

    Livewire::actingAs($owner)
        ->test('right-panel')
        ->assertDontSee('Passport views')
        ->assertDontSee('Recent viewers');
});

test('the passport page shows the total view count', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();
    $service = app(PassportViewService::class);
    $service->record($owner, $viewer);

    $this->actingAs($owner)
        ->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertSee('Passport views', false)
        ->assertSee('1', false);
});

test('verified owners see a compact viewer strip on the passport header', function () {
    $owner = User::factory()->create(['public_passport' => true, 'is_verified' => true, 'verified_at' => now()]);
    $viewer = User::factory()->create(['name' => 'Curious Casey']);
    app(PassportViewService::class)->record($owner, $viewer);

    $this->actingAs($owner)
        ->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertSee('Who viewed your profile')
        ->assertSee('Curious Casey')
        ->assertSee('1 viewer');
});

test('unverified owners see blurred viewers and a verify prompt in the header', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create(['name' => 'Secret Searcher']);
    app(PassportViewService::class)->record($owner, $viewer);

    $this->actingAs($owner)
        ->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertSee('Who viewed your profile')
        ->assertDontSee('Secret Searcher')
        ->assertSee('Verify to reveal');
});

test('the viewer strip is hidden when the passport has no recorded viewers', function () {
    $owner = User::factory()->create(['public_passport' => true, 'is_verified' => true, 'verified_at' => now()]);

    $this->actingAs($owner)
        ->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertDontSee('Who viewed your profile');
});

test('visitors never see the viewer strip on someone elses passport', function () {
    $owner = User::factory()->create(['public_passport' => true, 'is_verified' => true, 'verified_at' => now()]);
    $viewer = User::factory()->create(['name' => 'Snooping Sam', 'is_verified' => true, 'verified_at' => now()]);
    app(PassportViewService::class)->record($owner, $viewer);

    $this->actingAs($viewer)
        ->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertDontSee('Who viewed your profile');
});

test('passport capabilities and projects render skill logos', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel-'.uniqid(), 'category' => 'backend']);
    $owner->skills()->attach($skill, ['level' => 8]);
    Project::create([
        'user_id' => $owner->id,
        'title' => 'Payment Rails',
        'slug' => 'payment-rails-'.$owner->id,
        'problem' => 'Settlements were slow and hard to reconcile.',
        'solution' => 'A ledger service that reconciles every transaction in real time.',
        'tech_stack' => ['Laravel', 'Vue', 'Redis'],
        'status' => 'published',
        'published_at' => now(),
    ]);

    $this->get(route('passport', $owner->handle()))
        ->assertOk()
        ->assertSee('Capabilities')
        ->assertSee('Payment Rails')
        ->assertSee('viewBox="0 0 24 24"', false);
});

test('opening the passport flyout records a view', function () {
    $owner = User::factory()->create(['public_passport' => true]);
    $viewer = User::factory()->create();

    Livewire::withoutLazyLoading()
        ->actingAs($viewer)
        ->test('passport-flyout-body', ['userId' => $owner->id]);

    expect(PassportView::where('passport_owner_id', $owner->id)
        ->where('viewer_id', $viewer->id)
        ->exists())->toBeTrue();
});
