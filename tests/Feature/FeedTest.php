<?php

use App\Enums\TimelineEventType;
use App\Enums\UserRole;
use App\Enums\Visibility;
use App\Models\Skill;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Services\FeedService;

test('guests are redirected from the home feed', function () {
    $this->get(route('home'))->assertRedirect(route('login'));
});

test('the root path shows the landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('landing');
});

test('the for-companies landing page shows recruiter tools with live data', function () {
    $this->get(route('for-companies'))
        ->assertOk()
        ->assertSee('Hire engineers who can')
        ->assertSee('Evidence search')
        ->assertSee('Verified only')
        ->assertSee('Recruiter Intelligence Suite')
        ->assertSee('Questions from recruiters');
});

test('the for-companies search reads filter state from the url query string', function () {
    $this->get(route('for-companies').'?verified=1&online=1&q=laravel&loc=Kampala&skills=Laravel,PHP')
        ->assertOk()
        ->assertSee("params.get('verified') === '1'", false)
        ->assertSee("params.get('online') === '1'", false)
        ->assertSee("params.get('skills')", false)
        ->assertSee('history.replaceState', false);
});

test('the main landing page no longer contains the companies section', function () {
    $this->get('/')
        ->assertOk()
        ->assertDontSee('Hire engineers who can actually prove it')
        ->assertDontSee('See how it works for companies');
});

test('authenticated users can visit the home feed', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::feed');
});

test('recruiters can visit the home feed and see the feed link in the sidebar', function () {
    $recruiter = User::factory()->create(['role' => UserRole::Recruiter]);

    $this->actingAs($recruiter)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::feed')
        ->assertSee('Welcome back')
        ->assertSee($recruiter->name)
        ->assertSee('Home')
        ->assertSee(route('home'));
});

test('company accounts can visit the home feed', function () {
    $company = User::factory()->create(['role' => UserRole::Company]);

    $this->actingAs($company)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::feed');
});

test('verified users see the unread messages badge next to the sidebar messages link', function () {
    $user = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('unread-messages-badge');
});

test('unverified users do not see the messages badge or the messages link', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertDontSeeLivewire('unread-messages-badge');
});

test('the root path stays the landing page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/')
        ->assertOk()
        ->assertViewIs('landing');
});

test('the feed only surfaces events visible to the viewer', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();

    TimelineEvent::create([
        'user_id' => $other->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'Public event',
        'visibility' => Visibility::Public,
        'occurred_at' => now(),
    ]);

    TimelineEvent::create([
        'user_id' => $other->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'Private event',
        'visibility' => Visibility::Private,
        'occurred_at' => now(),
    ]);

    $events = app(FeedService::class)->feed($viewer);

    expect($events->pluck('title'))->toContain('Public event')
        ->and($events->pluck('title'))->not->toContain('Private event');
});

test('the feed uses infinite scroll instead of pagination links', function () {
    $viewer = User::factory()->create();
    $developers = User::factory()->count(25)->create();

    foreach ($developers as $i => $developer) {
        TimelineEvent::create([
            'user_id' => $developer->id,
            'type' => TimelineEventType::ProjectPublished,
            'title' => "Event {$i}",
            'visibility' => Visibility::Public,
            'occurred_at' => now()->subMinutes(25 - $i),
        ]);
    }

    $this->actingAs($viewer)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('wire:intersect.once.margin.200px="loadMore"', false)
        ->assertSee('feed-sentinel-20', false)
        ->assertDontSee('Previous');
});

test('the feed renders every event-type icon without crashing', function () {
    $user = User::factory()->create();

    foreach (TimelineEventType::cases() as $type) {
        TimelineEvent::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => "Event for {$type->value}",
            'visibility' => Visibility::Public,
            'occurred_at' => now()->subMinutes(rand(1, 300)),
        ]);
    }

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk();
});

test('the feed shows only the latest event per developer', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();

    foreach ([now()->subDays(5), now()->subDays(2), now()->subHours(1)] as $i => $occurredAt) {
        TimelineEvent::create([
            'user_id' => $other->id,
            'type' => TimelineEventType::ProjectPublished,
            'title' => "Event {$i}",
            'visibility' => Visibility::Public,
            'occurred_at' => $occurredAt,
        ]);
    }

    $events = app(FeedService::class)->feed($viewer);

    expect($events->count())->toBe(1)
        ->and($events->first()->title)->toBe('Event 2');
});

test('the feed dedupes per developer and keeps the newest event for each', function () {
    $viewer = User::factory()->create();
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    foreach ([
        ['title' => 'alice old', 'user' => $alice, 'occurred_at' => now()->subDays(4)],
        ['title' => 'alice mid', 'user' => $alice, 'occurred_at' => now()->subDays(1)],
        ['title' => 'alice new', 'user' => $alice, 'occurred_at' => now()->subMinutes(10)],
        ['title' => 'bob event', 'user' => $bob, 'occurred_at' => now()->subHours(2)],
    ] as $event) {
        TimelineEvent::create([
            'user_id' => $event['user']->id,
            'type' => TimelineEventType::ProjectPublished,
            'title' => $event['title'],
            'visibility' => Visibility::Public,
            'occurred_at' => $event['occurred_at'],
        ]);
    }

    $events = app(FeedService::class)->feed($viewer);

    expect($events->count())->toBe(2)
        ->and($events->pluck('title'))->toContain('alice new')
        ->and($events->pluck('title'))->toContain('bob event')
        ->and($events->pluck('title'))->not->toContain('alice old')
        ->and($events->pluck('title'))->not->toContain('alice mid');
});

test('the feed dedupe respects the type filter', function () {
    $viewer = User::factory()->create();
    $other = User::factory()->create();

    TimelineEvent::create([
        'user_id' => $other->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'New project event',
        'visibility' => Visibility::Public,
        'occurred_at' => now()->subHour(),
    ]);

    TimelineEvent::create([
        'user_id' => $other->id,
        'type' => TimelineEventType::VouchReceived,
        'title' => 'Newer vouch event',
        'visibility' => Visibility::Public,
        'occurred_at' => now()->subMinutes(5),
    ]);

    $events = app(FeedService::class)->feed($viewer, TimelineEventType::ProjectPublished);

    expect($events->count())->toBe(1)
        ->and($events->first()->title)->toBe('New project event');
});

test('the landing page feed shows only the latest event per developer', function () {
    $alice = User::factory()->create();

    TimelineEvent::create([
        'user_id' => $alice->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'Old landing event',
        'visibility' => Visibility::Public,
        'occurred_at' => now()->subDays(3),
    ]);

    TimelineEvent::create([
        'user_id' => $alice->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'New landing event',
        'visibility' => Visibility::Public,
        'occurred_at' => now()->subHour(),
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('New landing event')
        ->assertDontSee('Old landing event');
});

test('the feed can be filtered by event type', function () {
    $user = User::factory()->create();

    TimelineEvent::create([
        'user_id' => $user->id,
        'type' => TimelineEventType::ArticlePublished,
        'title' => 'An article event',
        'visibility' => Visibility::Public,
        'occurred_at' => now(),
    ]);

    TimelineEvent::create([
        'user_id' => $user->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'A project event',
        'visibility' => Visibility::Public,
        'occurred_at' => now(),
    ]);

    $events = app(FeedService::class)->feed($user, TimelineEventType::ArticlePublished);

    expect($events->pluck('title'))->toContain('An article event')
        ->and($events->pluck('title'))->not->toContain('A project event');
});

test('the growth sections render on the own passport', function () {
    $user = User::factory()->create(['public_passport' => true]);

    $this->actingAs($user)
        ->get(route('passport', $user->handle()))
        ->assertOk()
        ->assertSee('Current Level')
        ->assertSee('Engineering Streak')
        ->assertSee('Add evidence');
});

test('the passport renders for a user', function () {
    $user = User::factory()->create(['username' => 'passport-test', 'public_passport' => true]);

    $this->actingAs($user)
        ->get(route('passport', $user->handle()))
        ->assertOk();
});

test('the passport mirrors the scout profile layout', function () {
    $user = User::factory()->create([
        'username' => 'scout-look',
        'headline' => 'Full-stack engineer',
        'bio' => 'Ships Laravel products end to end.',
        'location' => 'Remote',
        'github_url' => 'https://github.com/scout-look',
        'public_passport' => true,
    ]);

    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $user->skills()->attach($skill->id, ['level' => 5, 'verified_at' => now()]);

    $this->actingAs($user)
        ->get(route('passport', $user->handle()))
        ->assertOk()
        ->assertSee('Public passport')
        ->assertSee('Summary')
        ->assertSee('Capabilities')
        ->assertSee('Reputation')
        ->assertSee('Projects');
});

test('users without onboarding see the completion banner on the feed', function () {
    $user = User::factory()->withoutOnboarding()->create();

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->assertSet('profileCompletion', function (int $value) {
            return $value >= 0 && $value <= 100;
        })
        ->assertSee('Profile completion')
        ->assertSee('Complete onboarding');
});

test('onboarded users do not see the completion banner', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->assertDontSee('Complete onboarding');
});

test('the completion banner disappears once the profile exceeds 75%', function () {
    $user = User::factory()->withoutOnboarding()->create([
        'headline' => 'Senior Laravel engineer',
        'bio' => 'Building thoughtful software.',
        'location' => 'Berlin, Germany',
        'github_url' => 'https://github.com/jane',
    ]);

    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel', 'category' => 'backend']);
    $user->skills()->attach($skill); // 5 of 6 checks → 83%

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->assertSet('profileCompletion', 83)
        ->assertDontSee('Complete onboarding');
});

test('the feed defaults to the list layout', function () {
    $user = User::factory()->create(['feed_layout' => 'list']);

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->assertSet('layout', 'list');
});

test('the feed uses the saved layout when rendering', function () {
    $user = User::factory()->create(['feed_layout' => 'grid']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::feed')
        ->assertSee('setLayout', false)
        ->assertSee('md:grid-cols-2', false);
});

test('switching the feed layout persists it to the database', function () {
    $user = User::factory()->create(['feed_layout' => 'list']);

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->call('setLayout', 'grid')
        ->assertSet('layout', 'grid');

    expect($user->fresh()->feed_layout)->toBe('grid');
});

test('the feed supports the compact layout', function () {
    $user = User::factory()->create(['feed_layout' => 'compact']);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::feed')
        ->assertSee('setLayout', false);

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->call('setLayout', 'compact')
        ->assertSet('layout', 'compact');

    expect($user->fresh()->feed_layout)->toBe('compact');
});

test('the feed shows the developer summary in the header', function () {
    $user = User::factory()->create([
        'headline' => 'Senior Engineer',
        'bio' => 'Building real things.',
        'streak_count' => 7,
    ]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee($user->name)
        ->assertSee('@'.$user->handle())
        ->assertSee('Streak', false)
        ->assertSee('7');
});

test('an invalid feed layout is ignored', function () {
    $user = User::factory()->create(['feed_layout' => 'list']);

    Livewire::actingAs($user)
        ->test('pages::feed')
        ->call('setLayout', 'weird')
        ->assertSet('layout', 'list');

    expect($user->fresh()->feed_layout)->toBe('list');
});
