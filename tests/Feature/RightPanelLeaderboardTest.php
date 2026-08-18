<?php

use App\Livewire\RightPanel;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Wirechat\Wirechat\Models\Conversation;

function leaderboardEngineer(string $name = 'Ava Builds'): User
{
    return User::factory()->create([
        'name' => $name,
        'experience_points' => 500,
        'reputation_score' => 900,
    ]);
}

test('verified users see a chat connect button in the top engineers modal', function () {
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $engineer = leaderboardEngineer('Jordan Sceptile');

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('Top 100 engineers')
        ->assertSee('Send a message')
        ->assertSeeHtml('wire:click="connect('.$engineer->id.')"');
});

test('unverified users do not see the leaderboard connect button', function () {
    $viewer = User::factory()->create();
    leaderboardEngineer('Jordan Sceptile');

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('Top 100 engineers')
        ->assertDontSee('Send a message');
});

test('the leaderboard does not offer a connect button for your own row', function () {
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $engineer = leaderboardEngineer('Jordan Sceptile');

    // Put the viewer themselves on the board too.
    $viewer->forceFill(['experience_points' => 600, 'reputation_score' => 950])->save();

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertSeeHtml('wire:click="connect('.$engineer->id.')"')
        ->assertDontSeeHtml('wire:click="connect('.$viewer->id.')"');
});

test('connecting from the leaderboard creates a private conversation and redirects to chat', function () {
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $engineer = leaderboardEngineer();

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->call('connect', $engineer->id)
        ->assertRedirect(route('wirechat.chats.chat', Conversation::firstOrFail()));

    expect(Conversation::count())->toBe(1)
        ->and($viewer->conversations()->count())->toBe(1)
        ->and($engineer->conversations()->count())->toBe(1);
});

test('unverified users are blocked from connecting from the leaderboard', function () {
    $viewer = User::factory()->create();
    $engineer = leaderboardEngineer();

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->call('connect', $engineer->id)
        ->assertNoRedirect();

    expect(Conversation::count())->toBe(0)
        ->and($viewer->conversations()->count())->toBe(0);
});

test('leaderboard rows flag engineers the viewer already chats with', function () {
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $engineer = leaderboardEngineer('Jordan Sceptile');
    $viewer->createConversationWith($engineer);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('You already chat with Jordan Sceptile')
        ->assertSee('Existing conversation');
});

test('leaderboard rows show no existing-chat indicator without a conversation', function () {
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    leaderboardEngineer('Jordan Sceptile');

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertDontSee('You already chat with')
        ->assertDontSee('Existing conversation')
        ->assertSee('Send a message');
});

test('verified users see a chat connect button next to online users', function () {
    Feature::for(null)->activate('public-presence');

    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $online = User::factory()->create([
        'last_activity_at' => now(),
        'experience_points' => 100,
        'reputation_score' => 400,
    ]);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('Online now')
        ->assertSee('Send a message')
        ->assertSeeHtml('wire:click="connect('.$online->id.')"');

    Feature::for(null)->deactivate('public-presence');
});

test('unverified users do not see a connect button next to online users', function () {
    Feature::for(null)->activate('public-presence');

    $viewer = User::factory()->create();
    User::factory()->create(['last_activity_at' => now()]);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('Online now')
        ->assertDontSee('Send a message');

    Feature::for(null)->deactivate('public-presence');
});
