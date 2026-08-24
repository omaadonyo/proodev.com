<?php

use App\Models\User;
use App\Services\SiteSettings;
use App\Services\SystemResetService;
use Livewire\Livewire;

test('admin can view and save SEO keywords', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.settings.seo')
        ->assertSee('SEO keywords')
        ->set('metaKeywords', '')
        ->call('save')
        ->assertHasErrors('metaKeywords');

    Livewire::actingAs($admin)
        ->test('pages::admin.settings.seo')
        ->set('metaKeywords', 'php, laravel, livewire, evidence engineering')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast-show');

    expect(app(SiteSettings::class)->metaKeywords())->toBe('php, laravel, livewire, evidence engineering');
});

test('public pages render the configured SEO keywords', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.settings.seo')
        ->set('metaKeywords', 'custom platform, engineering keywords')
        ->call('save');

    $response = $this->get(route('welcome'));

    $response->assertOk()->assertSee('name="keywords" content="custom platform, engineering keywords"', false);

    $response = $this->get(route('privacy'));

    $response->assertOk()->assertSee('name="keywords" content="custom platform, engineering keywords"', false);
});

test('system reset requires the reset password', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.settings.system')
        ->set('confirmation', 'wrong-password')
        ->call('runReset')
        ->assertDispatched('toast-show');
});

test('non-matching reset password never triggers the reset', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.settings.system')
        ->set('confirmation', 'MEI18')
        ->call('runReset');

    expect(app(SystemResetService::class)->counts()['users'])
        ->toBeGreaterThanOrEqual(1);
});
