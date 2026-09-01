<?php

use App\Models\User;
use Livewire\Livewire;

test('guests can open a verified developer short link', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'jane-doe',
    ]);

    $this->get(route('passport.short', 'jane-doe'))
        ->assertRedirect(route('devid', $user->handle()))
        ->assertSessionHasNoErrors();

    $this->get(route('passport.short', 'jane-doe'))
        ->assertRedirect();

    $this->followingRedirects()
        ->get(route('passport.short', 'jane-doe'))
        ->assertOk()
        ->assertSee($user->name);
});

test('the short link works with a custom short_domain', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'sam-codes',
    ]);

    $this->get(route('passport.short', 'sam-codes'))
        ->assertRedirect(route('devid', $user->handle()));

    $this->followingRedirects()
        ->get(route('passport.short', 'sam-codes'))
        ->assertOk()
        ->assertSee($user->name);
});

test('an unknown short link returns 404', function () {
    $this->get(route('passport.short', 'nobody-here'))
        ->assertNotFound();
});

test('the short link resolves the right user', function () {
    $other = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'sam-codes',
    ]);

    $this->get(route('passport.short', 'sam-codes'))
        ->assertRedirect(route('devid', $other->handle()));

    $this->followingRedirects()
        ->get(route('passport.short', 'sam-codes'))
        ->assertOk()
        ->assertSee($other->name)
        ->assertDontSee('jane-doe');
});

test('the short link falls back to a username when no short_domain is set', function () {
    $user = User::factory()->create(['username' => 'legacy-dev']);

    $this->get(route('passport.short', 'legacy-dev'))
        ->assertRedirect(route('devid', $user->handle()));
});

test('shortLink returns the short url for verified users', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'jane-doe',
    ]);

    expect($user->shortLink())->toBe(route('passport.short', 'jane-doe'));
});

test('shortLink is null for unverified users', function () {
    $user = User::factory()->create(['short_domain' => 'jane-doe']);

    expect($user->shortLink())->toBeNull();
});

test('shortLink is null when no short domain is reserved', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => null,
    ]);

    expect($user->shortLink())->toBeNull();
});

test('verified users can update their short link from profile settings', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'old-name',
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('short_domain', 'new-name')
        ->call('updateShortDomain')
        ->assertHasNoErrors();

    expect($user->fresh()->short_domain)->toBe('new-name');
});

test('short domain updates are validated', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'old-name',
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('short_domain', 'not allowed!')
        ->call('updateShortDomain')
        ->assertHasErrors('short_domain');

    expect($user->fresh()->short_domain)->toBe('old-name');
});

test('a short domain cannot be taken by another user', function () {
    User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'taken-name',
    ]);

    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'mine',
    ]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('short_domain', 'taken-name')
        ->call('updateShortDomain')
        ->assertHasErrors('short_domain');

    expect($user->fresh()->short_domain)->toBe('mine');
});

test('unverified users cannot update a short link', function () {
    $user = User::factory()->create(['short_domain' => null]);

    Livewire::actingAs($user)
        ->test('pages::settings.profile')
        ->set('short_domain', 'new-name')
        ->call('updateShortDomain')
        ->assertStatus(403);
});

test('the profile page shows the short link section for verified users only', function () {
    $verified = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'jane-doe',
    ]);

    Livewire::actingAs($verified)
        ->test('pages::settings.profile')
        ->assertOk()
        ->assertSee('Short DevID link')
        ->assertSee('/p/jane-doe');

    $plain = User::factory()->create();

    Livewire::actingAs($plain)
        ->test('pages::settings.profile')
        ->assertOk()
        ->assertDontSee('Short DevID link');
});
