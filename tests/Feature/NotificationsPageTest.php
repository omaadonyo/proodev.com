<?php

use App\Models\User;
use App\Notifications\WelcomeNotification;
use Livewire\Livewire;

test('guests are redirected from the notifications page', function () {
    $this->get(route('notifications'))->assertRedirect(route('login'));
});

test('authenticated users can view the notifications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('notifications'))
        ->assertOk()
        ->assertSeeLivewire('pages::notifications');
});

test('the notifications page lists the users notifications', function () {
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification($user));

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->assertSee('Welcome to ProoDev')
        ->assertSee('Mark all as read')
        ->assertSee('1');
});

test('marking a notification as read updates the list', function () {
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification($user));

    $notification = $user->notifications()->first();

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->call('markAsRead', $notification->id)
        ->assertHasNoErrors();

    expect($notification->fresh()->read_at)->not->toBeNull();
});

test('marking all notifications as read works', function () {
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification($user));
    $user->notify(new WelcomeNotification($user));

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->call('markAllAsRead')
        ->assertHasNoErrors();

    expect($user->unreadNotifications()->count())->toBe(0);
});

test('filtering notifications by unread', function () {
    $user = User::factory()->create();

    $user->notify(new WelcomeNotification($user));
    $read = $user->notifications()->first();
    $read->markAsRead();
    $user->notify(new WelcomeNotification($user));

    Livewire::actingAs($user)
        ->test('pages::notifications')
        ->call('setFilter', 'unread')
        ->assertHasNoErrors()
        ->assertCount('notifications', 1);
});

test('the bell links to the notifications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('notifications'));
});
