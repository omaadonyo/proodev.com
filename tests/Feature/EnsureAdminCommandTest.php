<?php

use App\Console\Commands\EnsureAdminCommand;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the ensure-admin command creates the platform admin with the default credentials', function () {
    $this->artisan(EnsureAdminCommand::class)
        ->expectsOutput('Created platform admin: adonyo@proodev.com')
        ->assertSuccessful();

    $admin = User::where('email', 'adonyo@proodev.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->is_admin)->toBeTrue()
        ->and(Hash::check('O+256M777007531A', $admin->password))->toBeTrue()
        ->and($admin->email_verified_at)->not->toBeNull()
        ->and($admin->username)->toBe('proodev-admin');
});

test('the ensure-admin command resets the password of an existing admin', function () {
    $admin = User::factory()->create([
        'email' => 'adonyo@proodev.com',
        'password' => Hash::make('old-password'),
        'is_admin' => true,
    ]);

    $this->artisan(EnsureAdminCommand::class)
        ->expectsOutput('Updated platform admin: adonyo@proodev.com')
        ->assertSuccessful();

    expect(Hash::check('O+256M777007531A', $admin->fresh()->password))->toBeTrue()
        ->and($admin->fresh()->is_admin)->toBeTrue();
});

test('the ensure-admin command accepts a custom email', function () {
    $this->artisan(EnsureAdminCommand::class, [
        '--email' => 'root@proodev.com',
    ])->assertSuccessful();

    $admin = User::where('email', 'root@proodev.com')->first();

    expect($admin)->not->toBeNull()
        ->and($admin->is_admin)->toBeTrue()
        ->and(Hash::check('O+256M777007531A', $admin->password))->toBeTrue();
});

test('the ensure-admin command demotes legacy admins so only one exists', function () {
    $legacy = User::factory()->create(['is_admin' => true]);
    $target = User::factory()->create(['is_admin' => true]);

    $this->artisan(EnsureAdminCommand::class, ['--email' => $target->email])
        ->expectsOutputToContain('legacy admin')
        ->assertSuccessful();

    expect(User::where('is_admin', true)->count())->toBe(1)
        ->and($legacy->fresh()->is_admin)->toBeFalse()
        ->and($target->fresh()->is_admin)->toBeTrue();
});
