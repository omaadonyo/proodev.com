<?php

use App\Models\User;

test('a missing page renders the custom 404 page', function () {
    $this->withoutVite()
        ->get('/this-page-does-not-exist-xyz')
        ->assertNotFound()
        ->assertSee('404')
        ->assertSee('Page not found')
        ->assertSee('Back to home')
        ->assertSee('Discover engineers');
});

test('the custom 404 page links to the app home', function () {
    $this->withoutVite()
        ->get('/definitely-not-a-route')
        ->assertNotFound()
        ->assertSee(route('welcome'))
        ->assertSee(route('jobs.index'));
});

test('a forbidden page renders the custom 403 page', function () {
    $user = User::factory()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('admin.sales'))
        ->assertForbidden()
        ->assertSee('403')
        ->assertSee('Access denied')
        ->assertSee('Back to home')
        ->assertSee('Log out')
        ->assertSee(route('logout'));
});
