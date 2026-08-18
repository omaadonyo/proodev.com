<?php

use App\Enums\TimelineEventType;
use App\Enums\Visibility;
use App\Models\Skill;
use App\Models\TimelineEvent;
use App\Models\User;

function timelineCardEventFor(User $user): TimelineEvent
{
    return TimelineEvent::create([
        'user_id' => $user->id,
        'type' => TimelineEventType::ProjectPublished,
        'title' => 'Shipped the analytics dashboard',
        'visibility' => Visibility::Public,
        'occurred_at' => now(),
    ]);
}

const PRESENCE_DOT = 'size-2.5 rounded-full border-2 border-white bg-emerald-500';

test('the feed list layout omits the level progress section', function () {
    $viewer = User::factory()->create(['feed_layout' => 'list']);
    timelineCardEventFor(User::factory()->create());

    $this->actingAs($viewer)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Shipped the analytics dashboard')
        ->assertDontSee('Level progress');
});

test('the feed grid layout keeps the level progress section', function () {
    $viewer = User::factory()->create(['feed_layout' => 'grid']);
    timelineCardEventFor(User::factory()->create());

    $this->actingAs($viewer)
        ->get(route('home'))
        ->assertOk()
        ->assertSee('Level progress');
});

test('online users get a presence dot on their feed avatar', function () {
    $online = User::factory()->create(['last_activity_at' => now()]);
    $event = timelineCardEventFor($online);

    $this->blade('<x-timeline-card :event="$event" />', ['event' => $event])
        ->assertSee(PRESENCE_DOT, false);
});

test('offline users do not get a presence dot', function () {
    $offline = User::factory()->create(); // last_activity_at is null
    $event = timelineCardEventFor($offline);

    $this->blade('<x-timeline-card :event="$event" />', ['event' => $event])
        ->assertDontSee(PRESENCE_DOT, false);
});

test('the list card omits the empty details row when there is no target', function () {
    $user = User::factory()->create();
    $event = timelineCardEventFor($user);

    $this->blade('<x-timeline-card :event="$event" />', ['event' => $event])
        ->assertDontSee('Open details')
        ->assertDontSee('level progress', false);
});

test('feed cards render skill logos instead of dots', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel-'.uniqid(), 'category' => 'backend']);
    $user->skills()->attach($skill, ['level' => 8]);
    $event = timelineCardEventFor($user);

    $this->blade('<x-timeline-card :event="$event" />', ['event' => $event])
        ->assertSee('Laravel')
        ->assertSee('viewBox="0 0 24 24"', false);
});

test('the passport modal renders on every layout even without a project target', function () {
    $user = User::factory()->create();
    $event = timelineCardEventFor($user);

    foreach ([[false, false], [true, false], [false, true]] as [$compact, $dense]) {
        $this->blade('<x-timeline-card :event="$event" :compact="'.var_export($compact, true).'" :dense="'.var_export($dense, true).'" />', ['event' => $event])
            ->assertSee('data-modal="open-'.$event->id.'"', false)
            ->assertSee('passport-flyout-body', false);
    }
});

test('the grid layout card stretches full width and stacks its stats', function () {
    $user = User::factory()->create();
    $event = timelineCardEventFor($user);

    $html = view('components.timeline-card', ['event' => $event, 'compact' => true])->render();

    expect($html)->toContain('flex h-full cursor-pointer flex-col');
    expect($html)->toContain('mt-auto');
    expect($html)->not->toContain('Lv '.$user->level().' · '.$user->levelTitle());
});
