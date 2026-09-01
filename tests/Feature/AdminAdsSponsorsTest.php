<?php

use App\Models\Ad;
use App\Models\Sponsor;
use App\Models\User;
use Livewire\Livewire;

test('the ads page requires an admin', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.ads'))->assertForbidden();
});

test('admins can open the add-ad modal from the create button', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->assertOk()
        ->assertSee('Add ad')
        ->assertSee('wire:model.self="showForm"', escape: false)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null);
});

test('admins can save a new ad from the modal', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->call('create')
        ->set('form.title', 'Hire top engineers')
        ->set('form.target_url', 'https://example.com')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(Ad::where('title', 'Hire top engineers')->exists())->toBeTrue();
});

test('admins can edit an existing ad from the modal', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $ad = Ad::factory()->create(['title' => 'Old title']);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->call('edit', $ad->id)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $ad->id)
        ->set('form.title', 'New title')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect($ad->fresh()->title)->toBe('New title');
});

test('admins can open the add-sponsor modal from the create button', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sponsors')
        ->assertOk()
        ->assertSee('Add sponsor')
        ->assertSee('wire:model.self="showForm"', escape: false)
        ->call('create')
        ->assertSet('showForm', true)
        ->assertSet('editingId', null);
});

test('admins can save a new sponsor from the modal', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sponsors')
        ->call('create')
        ->set('form.name', 'Acme Corp')
        ->set('form.website_url', 'https://acme.example')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect(Sponsor::where('name', 'Acme Corp')->exists())->toBeTrue();
});

test('admins can edit an existing sponsor from the modal', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sponsor = Sponsor::factory()->create(['name' => 'Old Corp']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sponsors')
        ->call('edit', $sponsor->id)
        ->assertSet('showForm', true)
        ->assertSet('editingId', $sponsor->id)
        ->set('form.name', 'New Corp')
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    expect($sponsor->fresh()->name)->toBe('New Corp');
});

test('admins can schedule a run window for an ad', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->call('create')
        ->set('form.title', 'Scheduled ad')
        ->set('form.starts_at', now()->subDay()->toDateString())
        ->set('form.ends_at', now()->addWeek()->toDateString())
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $ad = Ad::where('title', 'Scheduled ad')->firstOrFail();
    expect($ad->starts_at->toDateString())->toBe(now()->subDay()->toDateString());
    expect($ad->ends_at->toDateString())->toBe(now()->addWeek()->toDateString());
    expect($ad->runStatus())->toBe('running');
});

test('admins can schedule a run window for a sponsor', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sponsors')
        ->call('create')
        ->set('form.name', 'Scheduled Corp')
        ->set('form.starts_at', now()->addDay()->toDateString())
        ->call('save')
        ->assertSet('showForm', false)
        ->assertHasNoErrors();

    $sponsor = Sponsor::where('name', 'Scheduled Corp')->firstOrFail();
    expect($sponsor->starts_at->toDateString())->toBe(now()->addDay()->toDateString());
    expect($sponsor->runStatus())->toBe('upcoming');
});

test('an ad end date before its start date is rejected', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->call('create')
        ->set('form.title', 'Broken window')
        ->set('form.starts_at', now()->addWeek()->toDateString())
        ->set('form.ends_at', now()->toDateString())
        ->call('save')
        ->assertHasErrors(['form.ends_at']);

    expect(Ad::where('title', 'Broken window')->exists())->toBeFalse();
});

test('run window statuses render in the ads table', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Ad::factory()->create(['title' => 'Current run', 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
    Ad::factory()->create(['title' => 'Future run', 'starts_at' => now()->addDay()]);
    Ad::factory()->create(['title' => 'Past run', 'ends_at' => now()->subDay()]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->assertSee('Current run')
        ->assertSee('Running')
        ->assertSee('Future run')
        ->assertSee('Upcoming')
        ->assertSee('Past run')
        ->assertSee('Ended');
});
