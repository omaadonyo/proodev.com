<?php

use App\Models\User;

test('developer full flow: register then load onboarding page', function () {
    $response = $this->post(route('register.store'), [
        'role' => 'developer',
        'name' => 'Full Flow Dev',
        'email' => 'fullflow@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::where('email', 'fullflow@example.com')->first();
    $this->assertAuthenticatedAs($user);

    $this->actingAs($user)
        ->get(route('onboarding'))
        ->assertOk();
});

test('developer full flow: register then land on home', function () {
    $user = User::factory()->create(['onboarding_completed_at' => now()]);

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk();
});

test('developer full flow: company creation from register then dashboard', function () {
    $response = $this->post(route('register.store'), [
        'role' => 'company',
        'company_name' => 'Full Flow Inc',
        'name' => 'Full Flow Recruiter',
        'email' => 'fullflowco@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors();

    $user = User::where('email', 'fullflowco@example.com')->first();
    $company = $user->ownedCompany();

    expect($company)->not->toBeNull();

    $this->actingAs($user)
        ->get(route('companies.dashboard', $company))
        ->assertOk();
});
