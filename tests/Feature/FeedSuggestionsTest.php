<?php

use App\Livewire\FeedSuggestions;
use App\Models\FeatureRequest;
use App\Models\User;
use Livewire\Livewire;

test('feature requests start pending and are hidden until approved', function () {
    $user = User::factory()->create();

    FeatureRequest::create([
        'title' => 'Add dark mode schedules',
        'status' => FeatureRequest::STATUS_PENDING,
        'target_votes' => 2,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(FeedSuggestions::class)
        ->assertDontSee('Add dark mode schedules');

    expect(FeatureRequest::first()->status)->toBe('pending');
});

test('approved requests are visible and users can vote once', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $request = FeatureRequest::create([
        'title' => 'Keyboard shortcuts for the feed',
        'status' => FeatureRequest::STATUS_APPROVED,
        'target_votes' => 3,
        'created_by' => $user->id,
    ]);

    Livewire::actingAs($user)
        ->test(FeedSuggestions::class)
        ->assertSee('Keyboard shortcuts for the feed')
        ->call('vote', $request->id);

    expect($request->votes()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and($request->votes()->count())->toBe(1);

    // Voting again removes the vote (toggle).
    Livewire::actingAs($user)->test(FeedSuggestions::class)->call('vote', $request->id);

    expect($request->votes()->count())->toBe(0);

    // Another user votes.
    Livewire::actingAs($other)->test(FeedSuggestions::class)->call('vote', $request->id);

    expect($request->fresh()->votes()->count())->toBe(1);
});

test('reaching the vote target marks the request as included', function () {
    $user = User::factory()->create();
    $voters = User::factory()->count(2)->create();

    $request = FeatureRequest::create([
        'title' => 'Export DevID as PDF',
        'status' => FeatureRequest::STATUS_APPROVED,
        'target_votes' => 2,
        'created_by' => $user->id,
    ]);

    foreach ($voters as $voter) {
        Livewire::actingAs($voter)->test(FeedSuggestions::class)->call('vote', $request->id);
    }

    expect($request->fresh()->status)->toBe('included')
        ->and($request->fresh()->included_at)->not->toBeNull();
});

test('users can submit suggestions which stay pending until approved', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FeedSuggestions::class)
        ->set('title', 'Weekly goal reminders')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FeatureRequest::where('title', 'Weekly goal reminders')->first())
        ->not->toBeNull()
        ->and(FeatureRequest::where('title', 'Weekly goal reminders')->first()->status)->toBe('pending');
});
