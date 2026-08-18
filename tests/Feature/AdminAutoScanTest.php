<?php

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\AutoScanUrl;
use App\Models\Evidence;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('the auto-scan admin page requires login', function () {
    $this->get(route('admin.auto-scan'))->assertRedirect(route('login'));
});

test('non-admins are forbidden from the auto-scan admin page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.auto-scan'))->assertForbidden();
});

test('admins can view the auto-scan admin page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin)
        ->get(route('admin.auto-scan'))
        ->assertOk()
        ->assertSee('Auto-Scan')
        ->assertSee('Subscribers')
        ->assertSee('URL scan queue');
});

test('the summary counts subscribers and queued urls', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);
    $subscriber->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/private-repo',
        'status' => 'failed',
    ]);

    Payment::factory()->create([
        'user_id' => $subscriber->id,
        'purpose' => PaymentPurpose::AutoScan,
        'status' => PaymentStatus::Paid,
        'amount' => 8,
    ]);

    Payment::factory()->create([
        'user_id' => $subscriber->id,
        'purpose' => PaymentPurpose::AutoScan,
        'status' => PaymentStatus::Paid,
        'amount' => 8,
    ]);

    Payment::factory()->create([
        'user_id' => $subscriber->id,
        'purpose' => PaymentPurpose::AutoScan,
        'status' => PaymentStatus::Pending,
        'amount' => 8,
    ]);

    Payment::factory()->create([
        'user_id' => $subscriber->id,
        'purpose' => PaymentPurpose::AutoScan,
        'status' => PaymentStatus::Cancelled,
        'amount' => 8,
    ]);

    // Paid payments for other purposes must not count toward auto-scan revenue.
    Payment::factory()->paid()->verification()->create(['user_id' => $subscriber->id]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->assertOk()
        ->assertSet('summary.subscribers', 2)
        ->assertSet('summary.active', 2)
        ->assertSet('summary.queued_urls', 1)
        ->assertSet('summary.failed_urls', 1)
        ->assertSet('summary.revenue', 16.0)
        ->assertSet('summary.paid_payments', 2)
        ->assertSee('Lifetime revenue')
        ->assertSee('16.00');
});

test('the subscribers table lists auto-scan developers', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $subscriber = User::factory()->create([
        'name' => 'Scanner Person',
        'email' => 'scanner@example.com',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->assertOk()
        ->assertSee('Scanner Person')
        ->assertSee('1 queued');
});

test('the url queue shows per-url statuses', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);
    $subscriber->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/private-repo',
        'status' => 'failed',
        'last_error' => 'Repository not found or not publicly readable.',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->assertOk()
        ->assertSee('https://github.com/scanner/framework')
        ->assertSee('https://github.com/scanner/private-repo')
        ->assertSee('Queued')
        ->assertSee('Failed')
        ->assertSee('Retry');
});

test('admins can confirm a pending auto-scan payment', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::AutoScan,
        'status' => PaymentStatus::Pending,
        'amount' => 8,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('confirmPayment', $payment->id)
        ->assertHasNoErrors();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($user->fresh()->autoScanActive())->toBeTrue();
});

test('admins can cancel a pending auto-scan payment', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::AutoScan,
        'status' => PaymentStatus::Pending,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('cancelPayment', $payment->id)
        ->assertHasNoErrors();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Cancelled);
});

test('admins can run a scan for a subscriber', function () {
    Http::fake([
        'api.github.com/repos/scanner/framework' => Http::response([
            'name' => 'framework',
            'full_name' => 'scanner/framework',
            'description' => 'A PHP framework for fast APIs',
            'language' => 'PHP',
            'stargazers_count' => 100,
            'forks_count' => 40,
            'topics' => ['framework', 'php'],
            'homepage' => 'https://framework.dev',
            'html_url' => 'https://github.com/scanner/framework',
            'size' => 5000,
            'fork' => false,
            'archived' => false,
            'default_branch' => 'main',
            'created_at' => '2020-01-01T00:00:00Z',
            'updated_at' => '2024-01-01T00:00:00Z',
            'pushed_at' => '2024-06-01T00:00:00Z',
        ], 200),
        'api.github.com/repos/scanner/framework/readme' => Http::response([
            'content' => base64_encode("# Framework\n\nA documented project built with PHP."),
        ], 200),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('scanNow', $subscriber->id)
        ->assertHasNoErrors();

    expect(Evidence::where('user_id', $subscriber->id)->count())->toBe(1)
        ->and($subscriber->autoScanUrls()->first()->fresh()->status)->toBe('scanned')
        ->and($subscriber->autoScanRuns()->count())->toBe(1);

    $run = $subscriber->autoScanRuns()->first();

    expect($run->scanned)->toBe(1)
        ->and($run->new_evidence)->toBe(1)
        ->and($run->xp)->toBeGreaterThan(0)
        ->and($run->error)->toBeNull();
});

test('failed url scans are recorded as failed runs', function () {
    Http::fake([
        'api.github.com/repos/scanner/private-repo' => Http::response([], 404),
    ]);

    $admin = User::factory()->create(['is_admin' => true]);
    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber->autoScanUrls()->create(['url' => 'https://github.com/scanner/private-repo']);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('scanNow', $subscriber->id)
        ->assertHasNoErrors();

    $run = $subscriber->autoScanRuns()->first();

    expect($run)->not->toBeNull()
        ->and($run->scanned)->toBe(0)
        ->and($run->xp)->toBe(0)
        ->and($run->error)->not->toBeNull();
});

test('admins can open per-developer scan history with xp awarded', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $subscriber = User::factory()->create([
        'name' => 'History Person',
        'email' => 'history@example.com',
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $subscriber->autoScanRuns()->create([
        'scanned' => 3,
        'new_evidence' => 2,
        'new_projects' => 1,
        'new_journal' => 1,
        'xp' => 150,
    ]);

    $subscriber->autoScanRuns()->create([
        'scanned' => 1,
        'new_evidence' => 1,
        'new_projects' => 0,
        'new_journal' => 0,
        'xp' => 30,
    ]);

    $subscriber->autoScanRuns()->create([
        'scanned' => 0,
        'new_evidence' => 0,
        'new_projects' => 0,
        'new_journal' => 0,
        'xp' => 0,
        'error' => 'The queued repositories could not be fetched — check the URLs and try again.',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->assertSee('History · 3')
        ->call('showHistory', $subscriber->id)
        ->assertSet('historyUserId', $subscriber->id)
        ->assertSet('showHistoryModal', true)
        ->assertSee('Scan history')
        ->assertSee('wire:model.self="showHistoryModal"', escape: false)
        ->assertSee('+150 XP')
        ->assertSee('+30 XP')
        ->assertSee('Failed')
        ->assertSee('Total XP awarded by auto-scan')
        ->assertSee('+180 XP');
});

test('the scan history modal is empty for developers with no runs', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('showHistory', $subscriber->id)
        ->assertSee('No scans have run for this developer yet.');

    $component = Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('showHistory', $subscriber->id)
        ->call('closeHistory');

    expect($component->get('historyUserId'))->toBeNull()
        ->and($component->get('showHistoryModal'))->toBeFalse();
});

test('admins can retry a failed url', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $url = $subscriber->autoScanUrls()->create([
        'url' => 'https://github.com/scanner/private-repo',
        'status' => 'failed',
        'last_error' => 'Repository not found or not publicly readable.',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('retryUrl', $url->id)
        ->assertHasNoErrors();

    $url = $url->fresh();

    expect($url->status)->toBe('queued')
        ->and($url->last_error)->toBeNull();
});

test('admins can remove a url from the queue', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $subscriber = User::factory()->create([
        'auto_scan_enabled' => true,
        'auto_scan_active_until' => now()->addDays(30),
    ]);

    $url = $subscriber->autoScanUrls()->create(['url' => 'https://github.com/scanner/framework']);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('removeUrl', $url->id)
        ->assertHasNoErrors();

    expect(AutoScanUrl::whereKey($url->id)->exists())->toBeFalse();
});

test('admins can turn auto-scan on and off for a developer', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['auto_scan_enabled' => false]);

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('toggleAutoScan', $user->id)
        ->assertHasNoErrors();

    expect($user->fresh()->auto_scan_enabled)->toBeTrue();

    Livewire::actingAs($admin)
        ->test('pages::admin.auto-scan')
        ->call('toggleAutoScan', $user->id)
        ->assertHasNoErrors();

    expect($user->fresh()->auto_scan_enabled)->toBeFalse();
});
