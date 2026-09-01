<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\User;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'John Doe',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('onboarding', absolute: false));

    $this->assertAuthenticated();
});

test('developers can register even when an empty company name is submitted', function () {
    $response = $this->post(route('register.store'), [
        'role' => 'developer',
        'company_name' => '',
        'name' => 'Dev User',
        'email' => 'dev@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('onboarding', absolute: false));

    $user = User::where('email', 'dev@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Developer);
});

test('users can register as a company and are routed to company onboarding', function () {
    $response = $this->post(route('register.store'), [
        'role' => 'company',
        'company_name' => 'Acme Inc',
        'name' => 'Jane Recruiter',
        'email' => 'jane@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::where('email', 'jane@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::Company)
        ->and($user->isCompanyAccount())->toBeTrue();

    $company = Company::where('name', 'Acme Inc')->first();

    expect($company)->not->toBeNull()
        ->and($company->status)->toBe(CompanyStatus::Approved)
        ->and($company->plan)->toBe(CompanyPlan::Trial)
        ->and($company->canPostJobs())->toBeTrue()
        ->and($company->members()->where('user_id', $user->id)->exists())->toBeTrue();

    $response->assertRedirect(route('companies.onboarding', $company, absolute: false));
});

test('company registration requires a company name', function () {
    $this->post(route('register.store'), [
        'role' => 'company',
        'name' => 'Jane Recruiter',
        'email' => 'jane2@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors('company_name');

    $this->assertGuest();
});
