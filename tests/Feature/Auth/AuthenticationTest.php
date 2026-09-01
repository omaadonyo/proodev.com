<?php

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\User;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('password fields render the visibility toggle on login and register', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Toggle password visibility');

    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Toggle password visibility');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
});

test('company accounts are redirected to their company dashboard on login', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id, 'status' => CompanyStatus::Approved]);
    $company->members()->create(['user_id' => $owner->id, 'role' => 'owner']);

    $response = $this->post(route('login.store'), [
        'email' => $owner->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('companies.dashboard', $company, absolute: false));

    $this->assertAuthenticatedAs($owner);
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect('/');

    $this->assertGuest();
});
