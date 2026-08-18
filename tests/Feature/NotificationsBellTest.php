<?php

use App\Models\User;
use Livewire\Livewire;

test('the notifications bell shows a dropdown with recent notifications', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'New voucher', 'body' => 'Someone vouched for you'],
        'read_at' => null,
    ]);
    $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Old news', 'body' => 'A while ago'],
        'read_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('notifications-bell')
        ->assertSee('New voucher')
        ->assertSee('Old news')
        ->assertSee('View all notifications')
        ->assertSee('1');
});

test('the notifications bell can mark notifications as read', function () {
    $user = User::factory()->create();
    $notification = $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Unread', 'body' => 'Read me'],
        'read_at' => null,
    ]);
    $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Also unread', 'body' => 'Read me too'],
        'read_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test('notifications-bell')
        ->call('markAsRead', $notification->id)
        ->assertSet('unreadCount', 1);

    expect($notification->fresh()->read_at)->not->toBeNull();

    Livewire::actingAs($user)
        ->test('notifications-bell')
        ->call('markAllAsRead')
        ->assertSet('unreadCount', 0);
});

test('the notifications bell can clear all notifications', function () {
    $user = User::factory()->create();

    $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Unread one', 'body' => 'Body'],
        'read_at' => null,
    ]);
    $user->notifications()->create([
        'id' => Str::uuid()->toString(),
        'type' => 'App\Notifications\TestNotification',
        'data' => ['title' => 'Already read', 'body' => 'Body'],
        'read_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('notifications-bell')
        ->assertSee('Clear all')
        ->call('clearAll')
        ->assertSet('unreadCount', 0);

    expect($user->notifications()->count())->toBe(0);
});
