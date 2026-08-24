<?php

use App\Mail\ContactMessageMail;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

test('the about page renders with the contact form', function () {
    $this->get(route('about'))
        ->assertOk()
        ->assertSee('About ProoDev')
        ->assertSee('Contact us')
        ->assertSee('Build');
});

test('users can send a contact message to the admin via email', function () {
    Mail::fake();

    Livewire::test('pages::about')
        ->set('name', 'Jane Doe')
        ->set('email', 'jane@example.com')
        ->set('message', 'I would love to learn more about ProoDev for my team.')
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertQueued(ContactMessageMail::class, function ($mail) {
        return $mail->hasTo(config('platform.admin_email'))
            && $mail->senderName === 'Jane Doe'
            && $mail->senderEmail === 'jane@example.com';
    });
});

test('contact messages require a real message', function () {
    Mail::fake();

    Livewire::test('pages::about')
        ->set('name', 'Jane')
        ->set('email', 'jane@example.com')
        ->set('message', '')
        ->call('submit')
        ->assertHasErrors('message');

    Mail::assertNothingSent();
});

test('spammy messages are rejected without sending', function () {
    Mail::fake();

    Livewire::test('pages::about')
        ->set('name', 'Bot')
        ->set('email', 'bot@spam.com')
        ->set('message', 'Check out this casino bonus http://spam.example.com now!')
        ->call('submit');

    Mail::assertNothingSent();
});

test('the verified page lists verified developers only', function () {
    $verified = User::factory()->create([
        'name' => 'Sarah Ahmed',
        'is_verified' => true,
        'verified_at' => now(),
        'public_passport' => true,
        'reputation_score' => 500,
    ]);

    $unverified = User::factory()->create([
        'name' => 'Hidden Dev',
        'is_verified' => false,
        'public_passport' => true,
    ]);

    $this->get(route('verified'))
        ->assertOk()
        ->assertSee('Verified Developers')
        ->assertSee('Sarah Ahmed')
        ->assertDontSee('Hidden Dev');
});
