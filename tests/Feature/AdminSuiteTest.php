<?php

use App\Enums\CompanyPlan;
use App\Enums\CompanyStatus;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\BlockedIp;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Pennant\Feature;
use Livewire\Livewire;

test('the new admin pages require login', function () {
    foreach (['admin.subscriptions', 'admin.payments', 'admin.sales', 'admin.analytics'] as $route) {
        $this->get(route($route))->assertRedirect(route('login'));
    }
});

test('non-admins are forbidden from the new admin pages', function () {
    $user = User::factory()->create();

    foreach (['admin.subscriptions', 'admin.payments', 'admin.sales', 'admin.analytics'] as $route) {
        $this->actingAs($user)->get(route($route))->assertForbidden();
    }
});

test('admins can view the new admin pages', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    foreach (['admin.subscriptions', 'admin.payments', 'admin.sales', 'admin.analytics'] as $route) {
        $this->actingAs($admin)->get(route($route))->assertOk();
    }
});

test('isOnline reflects recent activity', function () {
    $online = User::factory()->create(['last_activity_at' => now()]);
    $stale = User::factory()->create(['last_activity_at' => now()->subMinutes(10)]);
    $never = User::factory()->create(['last_activity_at' => null]);

    expect($online->isOnline())->toBeTrue()
        ->and($stale->isOnline())->toBeFalse()
        ->and($never->isOnline())->toBeFalse();
});

test('the tracking middleware stamps last activity throttled to once per minute', function () {
    $user = User::factory()->create(['last_activity_at' => now()->subMinutes(10)]);

    $this->actingAs($user)->get(route('home'))->assertOk();

    $stamped = $user->fresh()->last_activity_at;

    expect($stamped)->not->toBeNull()
        ->and($stamped->isAfter(now()->subMinutes(2)))->toBeTrue();

    $this->actingAs($user)->get(route('home'))->assertOk();

    expect($user->fresh()->last_activity_at->equalTo($stamped))->toBeTrue();
});

test('suspended users are blocked from the platform', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $this->actingAs($user)->get(route('home'))->assertForbidden();
});

test('suspended users can be reinstated', function () {
    $user = User::factory()->create(['suspended_at' => now()]);

    $user->unsuspend();

    expect($user->fresh()->isSuspended())->toBeFalse();

    $this->actingAs($user)->get(route('home'))->assertOk();
});

test('admins are not blocked by the suspension middleware', function () {
    $admin = User::factory()->create(['is_admin' => true, 'suspended_at' => now()]);

    $this->actingAs($admin)->get(route('home'))->assertOk();
});

test('admins cannot suspend their own account', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleSuspend', $admin->id)
        ->assertHasNoErrors();

    expect($admin->fresh()->isSuspended())->toBeFalse();
});

test('admins can verify users and adjust balances', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_verified' => false, 'credit_balance' => 0]);

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('toggleVerify', $user->id)
        ->assertHasNoErrors();

    expect($user->fresh()->isVerified())->toBeTrue();

    Livewire::actingAs($admin)
        ->test('pages::admin.users')
        ->call('adjustCredits', $user->id, 'credit_balance', 1)
        ->assertHasNoErrors();

    expect($user->fresh()->credit_balance)->toBe(10);
});

test('admins land on the admin dashboard after login', function () {
    $admin = User::factory()->create([
        'is_admin' => true,
        'onboarding_completed_at' => now(),
    ]);

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.index'));
});

test('admins can visit the home feed', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('home'))
        ->assertOk()
        ->assertSeeLivewire('pages::feed');
});

test('the admin overview shows the online now panel', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->create(['last_activity_at' => now()]);

    $this->actingAs($admin)
        ->get(route('admin.index'))
        ->assertOk()
        ->assertSee('Online now');
});

test('the subscriptions admin page lists active companies', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create();

    $company = Company::factory()->create([
        'owner_id' => $owner->id,
        'status' => CompanyStatus::Approved,
        'plan' => CompanyPlan::Intelligence,
    ]);

    Payment::factory()->create([
        'user_id' => $owner->id,
        'company_id' => $company->id,
        'purpose' => PaymentPurpose::Subscription,
        'status' => PaymentStatus::Paid,
        'amount' => 599,
        'metadata' => ['plan' => 'intelligence', 'first_month' => true],
        'paid_at' => Carbon::now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.subscriptions'))
        ->assertOk()
        ->assertSee($company->name)
        ->assertSee('Recruiter Intelligence Suite');
});

test('public presence indicators are gated by the feature flag', function () {
    $user = User::factory()->create();
    User::factory()->create(['last_activity_at' => now(), 'role' => 'developer']);

    Feature::for(null)->deactivate('public-presence');

    $this->actingAs($user)->get(route('home'))->assertDontSee('Online now');

    Feature::for(null)->activate('public-presence');

    $this->actingAs($user)->get(route('home'))->assertOk()->assertSee('Online now');
});

test('the analytics page shows summary statistics', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee('Total users')
        ->assertSee('Blocked IPs')
        ->assertSee('Users log');
});

test('login events are recorded for the analytics users log', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    expect(AuditLog::where('action', 'login')->count())->toBe(1);

    $this->actingAs($admin)
        ->get(route('admin.analytics'))
        ->assertOk()
        ->assertSee($user->name);
});

test('admins can block and unblock IP addresses', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.analytics')
        ->set('newIp', '203.0.113.7')
        ->set('newIpReason', 'Abusive traffic')
        ->call('blockIp')
        ->assertHasNoErrors();

    expect(BlockedIp::where('ip_address', '203.0.113.7')->exists())->toBeTrue();

    $blocked = BlockedIp::where('ip_address', '203.0.113.7')->firstOrFail();

    Livewire::actingAs($admin)
        ->test('pages::admin.analytics')
        ->call('unblockIp', $blocked->id)
        ->assertHasNoErrors();

    expect(BlockedIp::where('ip_address', '203.0.113.7')->exists())->toBeFalse();
});

test('blocked IP addresses are rejected by middleware', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    BlockedIp::create([
        'ip_address' => '203.0.113.9',
        'blocked_by' => $admin->id,
    ]);

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])
        ->actingAs($admin)
        ->get(route('home'))
        ->assertForbidden();
});

test('blocking an invalid IP shows a warning', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.analytics')
        ->set('newIp', 'not-an-ip')
        ->call('blockIp');

    expect(BlockedIp::count())->toBe(0);
});

test('the subscriptions page lists active companies and payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create();

    $company = Company::factory()->create([
        'owner_id' => $owner->id,
        'status' => CompanyStatus::Approved,
        'plan' => CompanyPlan::Intelligence,
    ]);

    Payment::factory()->create([
        'user_id' => $owner->id,
        'company_id' => $company->id,
        'purpose' => PaymentPurpose::Subscription,
        'status' => PaymentStatus::Paid,
        'amount' => 599,
        'metadata' => ['plan' => 'intelligence', 'first_month' => true],
        'paid_at' => Carbon::now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.subscriptions'))
        ->assertOk()
        ->assertSee($company->name)
        ->assertSee('Recruiter Intelligence Suite')
        ->assertSee('Monthly recurring revenue');
});

test('admins can confirm pending subscription payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create();

    $company = Company::factory()->create([
        'owner_id' => $owner->id,
        'status' => CompanyStatus::Approved,
        'plan' => CompanyPlan::Recruiter,
    ]);

    $payment = Payment::factory()->create([
        'user_id' => $owner->id,
        'company_id' => $company->id,
        'purpose' => PaymentPurpose::Subscription,
        'status' => PaymentStatus::Pending,
        'amount' => 299,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.subscriptions')
        ->call('markPaid', $payment->id)
        ->assertHasNoErrors();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('the reports page shows system features and usage', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(2)->create(['is_verified' => true]);

    Feature::for(null)->activate('public-presence');

    $this->actingAs($admin)
        ->get(route('admin.reports'))
        ->assertOk()
        ->assertSee('System features')
        ->assertSee('Usage overview')
        ->assertSee('Credits')
        ->assertSee('Public presence');
});

test('the reports feature table filters by search', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.reports')
        ->set('featureSearch', 'credits')
        ->assertHasNoErrors()
        ->assertSee('Credits')
        ->assertDontSee('Battles');
});

test('the users page lists role and balance filters', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.users'))
        ->assertOk()
        ->assertSee('Total users')
        ->assertSee('Verified developers');
});

test('the payments page lists summary cards and a searchable table', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Payment::factory()->create([
        'user_id' => $admin->id,
        'status' => PaymentStatus::Pending,
        'amount' => 25,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('Pending confirmations')
        ->assertSee('Mark paid')
        ->assertSee('Lifetime revenue');
});
