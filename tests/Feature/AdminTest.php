<?php

use App\Models\User;

test('non-admins are forbidden from the admin area', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.index'))
        ->assertForbidden();
});

test('admins can view the admin overview', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.index'))
        ->assertOk();
});

test('admins can view the moderation sub-pages', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach ([
        'admin.verifications',
        'admin.vouches',
        'admin.reports',
        'admin.plagiarism',
        'admin.users',
        'admin.companies',
        'admin.ai',
    ] as $route) {
        $this->actingAs($admin)
            ->get(route($route))
            ->assertOk();
    }
});

test('admin routes require login', function () {
    $this->get(route('admin.index'))->assertRedirect(route('login'));
});
