<?php

use App\Actions\Vouches\ApproveVouchAction;
use App\Actions\Vouches\CreateVouchAction;
use App\Data\VouchData;
use App\Enums\VouchStatus;
use App\Enums\VouchType;
use App\Models\Skill;
use App\Models\User;

test('guests are redirected from the vouches page', function () {
    $this->get(route('vouches'))->assertRedirect(route('login'));
});

test('an authenticated user can view the vouches page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('vouches'))
        ->assertOk();
});

test('creating a vouch consumes a credit', function () {
    $voucher = User::factory()->create(['vouch_credits' => 3]);
    $vouchee = User::factory()->create();

    app(CreateVouchAction::class)->handle($voucher, VouchData::fromArray([
        'vouchee_id' => $vouchee->id,
        'type' => VouchType::Skill,
        'message' => 'Great work',
    ]));

    expect($voucher->fresh()->vouch_credits)->toBe(2);

    $this->assertDatabaseHas('vouches', [
        'voucher_id' => $voucher->id,
        'vouchee_id' => $vouchee->id,
        'status' => 'pending',
    ]);
});

test('a user cannot vouch for themselves', function () {
    $user = User::factory()->create(['vouch_credits' => 2]);

    expect(fn () => app(CreateVouchAction::class)->handle($user, VouchData::fromArray([
        'vouchee_id' => $user->id,
        'type' => VouchType::Skill,
    ])))->toThrow(DomainException::class);
});

test('a user without credits cannot vouch', function () {
    $voucher = User::factory()->create(['vouch_credits' => 0]);
    $vouchee = User::factory()->create();

    expect(fn () => app(CreateVouchAction::class)->handle($voucher, VouchData::fromArray([
        'vouchee_id' => $vouchee->id,
        'type' => VouchType::Skill,
    ])))->toThrow(DomainException::class);
});

test('approving a vouch moves it to approved and credits the voucher on rejection', function () {
    $voucher = User::factory()->create(['vouch_credits' => 2]);
    $vouchee = User::factory()->create();

    $vouch = app(CreateVouchAction::class)->handle($voucher, VouchData::fromArray([
        'vouchee_id' => $vouchee->id,
        'type' => VouchType::Architecture,
        'message' => 'Strong design instincts',
    ]));

    app(ApproveVouchAction::class)->handle($vouch, true);

    expect($vouch->fresh()->status)->toBe(VouchStatus::Approved);

    app(ApproveVouchAction::class)->handle($vouch, false);

    expect($vouch->fresh()->status)->toBe(VouchStatus::Rejected)
        ->and($voucher->fresh()->vouch_credits)->toBe(2);
});

test('a pending vouch is not publicly visible', function () {
    $voucher = User::factory()->create(['vouch_credits' => 2]);
    $vouchee = User::factory()->create();
    $stranger = User::factory()->create();

    $vouch = app(CreateVouchAction::class)->handle($voucher, VouchData::fromArray([
        'vouchee_id' => $vouchee->id,
        'type' => VouchType::Skill,
    ]));

    expect($stranger->can('view', $vouch))->toBeFalse();
    expect($vouchee->can('view', $vouch))->toBeTrue();
});

test('a skill can be attached to a vouch', function () {
    $voucher = User::factory()->create(['vouch_credits' => 2]);
    $vouchee = User::factory()->create();
    $skill = Skill::create(['name' => 'Laravel', 'slug' => 'laravel']);

    $vouch = app(CreateVouchAction::class)->handle($voucher, VouchData::fromArray([
        'vouchee_id' => $vouchee->id,
        'type' => VouchType::Skill,
        'skill_id' => $skill->id,
    ]));

    expect($vouch->fresh()->skill_id)->toBe($skill->id);
});
