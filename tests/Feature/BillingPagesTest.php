<?php

use App\Enums\CreditTransactionType;
use App\Enums\PaymentMethod;
use App\Models\Skill;
use App\Models\User;
use App\Services\CreditService;
use App\Services\Payments\PaymentMethodSettings;
use Livewire\Livewire;

test('guests are redirected from the credits page', function () {
    $this->get(route('credits'))->assertRedirect(route('login'));
});

test('guests are redirected from the verification page', function () {
    $this->get(route('verify'))->assertRedirect(route('login'));
});

test('authenticated users can visit the credits page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('credits'))
        ->assertOk()
        ->assertSeeLivewire('pages::credits');
});

test('credits page shows today free allowance usage', function () {
    $user = User::factory()->create([
        'daily_evidence_count' => 2,
        'daily_evidence_date' => now()->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSet('usedToday', 2)
        ->assertSet('freeRemaining', 1)
        ->assertSet('freeUsagePercent', 67);
});

test('credits page resets usage display when the daily date differs', function () {
    $user = User::factory()->create([
        'daily_evidence_count' => 2,
        'daily_evidence_date' => now()->subDay()->toDateString(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertSet('usedToday', 0)
        ->assertSet('freeRemaining', 3)
        ->assertSet('freeUsagePercent', 0);
});

test('credits page tracks lifetime and monthly consumption', function () {
    $user = User::factory()->create();
    app(CreditService::class)->grant($user, 20, CreditTransactionType::Grant);
    app(CreditService::class)->spend($user, 5, CreditTransactionType::Submission);
    app(CreditService::class)->spend($user, 3, CreditTransactionType::Analysis);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSet('lifetimeEarned', 20)
        ->assertSet('lifetimeSpent', 8)
        ->assertSet('spentThisMonth', 8)
        ->assertSet('consumptionPercent', 29);
});

test('credits page renders weekly usage chart and transaction history', function () {
    $user = User::factory()->create();
    app(CreditService::class)->grant($user, 10, CreditTransactionType::Grant);
    app(CreditService::class)->spend($user, 4, CreditTransactionType::Submission);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Weekly usage')
        ->assertSee('Transaction history');
});

test('credits page shows the payment methods card with logos and how to pay', function () {
    config()->set('payments.methods.flutterwave.public_key', 'pk_test');
    config()->set('payments.methods.flutterwave.secret_key', 'sk_test');

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Payment methods')
        ->assertSee('Flutterwave')
        ->assertSee('WorldRemit')
        ->assertDontSee('Bank Transfer')
        ->assertDontSee('Pesapal')
        ->assertSee('Pay instantly with Visa, Mastercard')
        ->assertSee('MTN Mobile Money')
        ->assertSee('0786634306')
        ->assertSee('Emmanuel Adonyo')
        ->assertSee('Pay to')
        ->assertSee('Uganda')
        ->assertSee('Pay with any of these at checkout')
        ->assertSee('Secure checkout')
        ->assertSee('never stores your credit card or payment details');
});

test('disabled payment methods are hidden from the credits card', function () {
    $user = User::factory()->create();
    app(PaymentMethodSettings::class)->update(PaymentMethod::Pesapal, false, []);

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->assertOk()
        ->assertSee('Payment methods')
        ->assertDontSee('pesapal');
});

test('verification page shows readiness checklist and progress', function () {
    $user = User::factory()->create();
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel']);
    $user->skills()->attach($skill->id, ['level' => 3]);

    Livewire::actingAs($user)
        ->test('pages::verify')
        ->assertOk()
        ->assertSee('Verification readiness')
        ->assertSee('Skills on your DevID')
        ->assertSee('Verification readiness')
        ->assertSee('% ready')
        ->assertSee('Secure checkout')
        ->assertSee('never stores your credit card or payment details');
});

test('verified users see their short link and passport strength', function () {
    $user = User::factory()->create([
        'is_verified' => true,
        'verified_at' => now(),
        'short_domain' => 'jane-doe',
    ]);

    Livewire::actingAs($user)
        ->test('pages::verify')
        ->assertOk()
        ->assertSee('/p/jane-doe')
        ->assertSee('Copy link')
        ->assertSee('DevID strength');
});
