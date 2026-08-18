<?php

use App\Models\Ad;
use App\Models\Sponsor;
use App\Models\User;
use Livewire\Livewire;

test('the feed right panel shows an ads card with active ads instead of trending technologies', function () {
    $user = User::factory()->create();
    Ad::factory()->create(['title' => 'Visible ad', 'is_active' => true]);
    Ad::factory()->create(['title' => 'Hidden ad', 'is_active' => false]);

    Livewire::actingAs($user)
        ->test('right-panel')
        ->assertSee('Advertisement')
        ->assertSee('Visible ad')
        ->assertDontSee('Hidden ad')
        ->assertDontSee('Trending Technologies');
});

test('the feed right panel shows an ad placeholder when no ads exist', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('right-panel')
        ->assertSee('Advertisement')
        ->assertSee('Your ad here')
        ->assertSee('Advertise with ProoDev');
});

test('the top engineers card exposes the top 100 leaderboard modal', function () {
    User::factory()->count(12)->create(['experience_points' => 100]);
    $top = User::query()->orderByDesc('reputation_score')->first();

    Livewire::actingAs(User::factory()->create())
        ->test('right-panel')
        ->assertSee('Top Engineers')
        ->assertSee('Top 100')
        ->assertSee('top-engineers')
        ->assertSee($top->name);
});

test('the feed right panel shows active sponsors and the become a sponsor option', function () {
    $user = User::factory()->create();
    Sponsor::factory()->create(['name' => 'Acme Corp', 'is_active' => true]);
    Sponsor::factory()->create(['name' => 'Retired Inc', 'is_active' => false]);

    Livewire::actingAs($user)
        ->test('right-panel')
        ->assertSee('Our Sponsors')
        ->assertSee('Acme Corp')
        ->assertDontSee('Retired Inc')
        ->assertSee('Become a sponsor');
});

test('ads outside their run window are hidden from the feed', function () {
    $user = User::factory()->create();
    Ad::factory()->create(['title' => 'Running ad', 'is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
    Ad::factory()->create(['title' => 'Upcoming ad', 'is_active' => true, 'starts_at' => now()->addDay()]);
    Ad::factory()->create(['title' => 'Expired ad', 'is_active' => true, 'ends_at' => now()->subDay()]);
    Ad::factory()->create(['title' => 'Untimed ad', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test('right-panel')
        ->assertSee('Running ad')
        ->assertSee('Untimed ad')
        ->assertDontSee('Upcoming ad')
        ->assertDontSee('Expired ad');
});

test('sponsors outside their run window are hidden from the feed', function () {
    $user = User::factory()->create();
    Sponsor::factory()->create(['name' => 'Running Corp', 'is_active' => true, 'starts_at' => now()->subDay(), 'ends_at' => now()->addDay()]);
    Sponsor::factory()->create(['name' => 'Expired Corp', 'is_active' => true, 'ends_at' => now()->subDay()]);
    Sponsor::factory()->create(['name' => 'Untimed Corp', 'is_active' => true]);

    Livewire::actingAs($user)
        ->test('right-panel')
        ->assertSee('Running Corp')
        ->assertSee('Untimed Corp')
        ->assertDontSee('Expired Corp');
});

test('guests cannot access the admin ads page', function () {
    $this->get(route('admin.ads'))->assertRedirect(route('login'));
});

test('admins can manage ads', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->assertSee('Ads')
        ->call('create')
        ->set('form.title', 'Featured sponsor')
        ->set('form.target_url', 'https://example.com')
        ->call('save')
        ->assertHasNoErrors();

    expect(Ad::where('title', 'Featured sponsor')->exists())->toBeTrue();

    $ad = Ad::where('title', 'Featured sponsor')->first();

    Livewire::actingAs($admin)
        ->test('pages::admin.ads')
        ->call('toggle', $ad->id)
        ->assertHasNoErrors()
        ->call('delete', $ad->id)
        ->assertHasNoErrors();

    expect(Ad::where('title', 'Featured sponsor')->exists())->toBeFalse();
});

test('admins can manage sponsors', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sponsors')
        ->assertSee('Sponsors')
        ->call('create')
        ->set('form.name', 'Acme Corp')
        ->set('form.tagline', 'We build things')
        ->call('save')
        ->assertHasNoErrors();

    expect(Sponsor::where('name', 'Acme Corp')->exists())->toBeTrue();

    $sponsor = Sponsor::where('name', 'Acme Corp')->first();

    Livewire::actingAs($admin)
        ->test('pages::admin.sponsors')
        ->call('toggle', $sponsor->id)
        ->assertHasNoErrors()
        ->call('delete', $sponsor->id)
        ->assertHasNoErrors();

    expect(Sponsor::where('name', 'Acme Corp')->exists())->toBeFalse();
});
