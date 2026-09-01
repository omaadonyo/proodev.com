<?php

use App\Livewire\RightPanel;
use App\Models\User;
use Laravel\Pennant\Feature;
use Livewire\Livewire;
use Wirechat\Wirechat\Models\Conversation;

function leaderboardEngineer(string $name = 'Ava Builds', bool $verified = true): User
{
    return User::factory()->create([
        'name' => $name,
        'experience_points' => 500,
        'reputation_score' => 900,
        'is_verified' => $verified,
        'verified_at' => $verified ? now() : null,
    ]);
}

test('verified users see a chat connect button in the top engineers modal', function () {
    $viewer = User::factory()->create(['is_verified' => true, 'verified_at' => now()]);
    $engineer = leaderboardEngineer('Jordan Sceptile', verified: true);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('Top 100 engineers')
        ->assertSee('Chat with verified')
        ->assertSeeHtml('wire:click="connect('.$engineer->id.')"');
});

test('unverified users see a limited chat button for verified engineers', function () {
    $viewer = User::factory()->create();
    leaderboardEngineer('Jordan Sceptile', verified: true);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertSee('Top 100 engineers')
        ->assertSee('Chat with verified');
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

test('unverified users can connect once to a verified engineer via free streak, then are blocked', function () {
    $viewer = User::factory()->create([
        'two_hour_streak_count' => 1,
        'last_two_hour_reward_at' => now(),
    ]);
    $engineer = leaderboardEngineer(verified: true);
    $engineer2 = leaderboardEngineer('Second Verified', verified: true);

    // First connect succeeds (1 free streak, earned after 2 hours)
    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->call('connect', $engineer->id)
        ->assertRedirect(route('wirechat.chats.chat', Conversation::firstOrFail()));

    expect(Conversation::count())->toBe(1);

    // Second connect is blocked (streak consumed, need another 2 hours or verify)
    Livewire::actingAs($viewer->fresh())
        ->test(RightPanel::class)
        ->call('connect', $engineer2->id)
        ->assertRedirect(route('verify'));

    expect(Conversation::count())->toBe(1);
});

test('unverified users are blocked from chatting with unverified engineers', function () {
    $viewer = User::factory()->create();
    $engineer = leaderboardEngineer(verified: false);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->call('connect', $engineer->id)
        ->assertNoRedirect();

    expect(Conversation::count())->toBe(0);
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
    leaderboardEngineer('Jordan Sceptile', verified: true);

    Livewire::actingAs($viewer)
        ->test(RightPanel::class)
        ->assertOk()
        ->assertDontSee('You already chat with')
        ->assertDontSee('Existing conversation')
        ->assertSee('Chat with verified');
});

test('online now section has been removed from right panel', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(RightPanel::class)
        ->assertOk()
        ->assertDontSee('Online now')
        ->assertDontSee('No one is online right now');
});
