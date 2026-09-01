<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

function salesAdmin(): User
{
    return User::factory()->create(['is_admin' => true]);
}

test('the sales dashboard tracks daily, weekly, monthly and quarterly periods', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create(['user_id' => $admin->id, 'amount' => 50, 'currency' => 'USD']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee('Daily')
        ->assertSee('Weekly')
        ->assertSee('Monthly')
        ->assertSee('Quarterly')
        ->assertSee('Yearly')
        ->assertSee('Revenue · This month')
        ->assertSee('Top purpose')
        ->assertSee('Top method');
});

test('period stats reflect the selected period', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 25,
        'currency' => 'USD',
        'paid_at' => now()->subMonths(2),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('setPeriod', 'month')
        ->assertSet('period', 'month')
        ->assertSet('periodStats.revenue', 0.0)
        ->call('setPeriod', 'all')
        ->assertSet('period', 'all')
        ->assertSet('periodStats.revenue', 25.0)
        ->assertSet('periodStats.count', 1);
});

test('the sales dashboard shows a period-aware revenue sparkline', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 25,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee('Revenue trend · This month')
        ->assertSee('Per day')
        ->assertSee('peak')
        ->assertSee('Paid')
        ->assertSee('Pending')
        ->assertSee('Line')
        ->assertSee('Area')
        ->assertSee('Bars')
        ->assertSee('<path', false);
});

test('the revenue trend switches between line, area and bars layouts', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 25,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertSet('chartStyle', 'line')
        ->assertSee('stroke="#3750eb"', false)
        ->call('setChartStyle', 'area')
        ->assertSet('chartStyle', 'area')
        ->assertSee('opacity="0.12"', false)
        ->call('setChartStyle', 'bars')
        ->assertSet('chartStyle', 'bars')
        ->assertSee('<rect', false)
        ->assertDontSee('stroke="#3750eb"', false);
});

test('the trend chart carries per-bucket tooltip data with paid and pending amounts', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 25,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee('buckets: JSON.parse', false)
        ->assertSee('\u0022paid\u0022:\u002225.00 USD\u0022', false)
        ->assertSee('\u0022pending\u0022:\u00220.00 USD\u0022', false)
        ->assertSee('bucket.paid', false)
        ->assertSee('bucket.pending', false);
});

test('the revenue trend shows paid and pending series', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 25,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);
    Payment::factory()->create([
        'user_id' => $admin->id,
        'amount' => 10,
        'currency' => 'USD',
        'status' => PaymentStatus::Pending,
    ]);

    $component = Livewire::actingAs($admin)->test('pages::admin.sales')->assertOk();
    $trend = $component->instance()->dailyRevenue;

    expect($trend['paid_total'])->toBe(25.0)
        ->and($trend['pending_total'])->toBe(10.0)
        ->and(collect($trend['points'])->sum('paid'))->toBe(25.0)
        ->and(collect($trend['points'])->sum('pending'))->toBe(10.0);
});

test('the sparkline switches granularity with the selected period', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 10,
        'currency' => 'USD',
        'paid_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('setPeriod', 'today')
        ->assertSee('Revenue trend · Today')
        ->assertSee('Hourly')
        ->call('setPeriod', 'quarter')
        ->assertSee('Revenue trend · This quarter')
        ->assertSee('Per week')
        ->call('setPeriod', 'all')
        ->assertSee('Revenue trend · All time')
        ->assertSee('Per month');
});

test('the sparkline shows an empty state without revenue', function () {
    $admin = salesAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee('Revenue trend · This month')
        ->assertSee('No revenue recorded in this period yet.')
        ->assertDontSee('stroke="#3750eb"', false)
        ->assertDontSee('fill="#3750eb"', false);
});

test('the admin can switch the sales dashboard currency to UGX', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create(['user_id' => $admin->id, 'amount' => 50, 'currency' => 'USD']);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('setCurrency', 'ugx')
        ->assertSet('currency', 'ugx')
        ->assertSee('UGX')
        ->assertDontSee('50.00 USD');
});

test('the currency switcher persists on the admin account across pages', function () {
    $admin = salesAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('setCurrency', 'ugx');

    expect($admin->fresh()->billing_currency)->toBe('ugx');

    $this->actingAs($admin)
        ->get(route('admin.payments'))
        ->assertOk()
        ->assertSee('UGX');
});

test('the preferred currency is stored per account, not per session', function () {
    $first = salesAdmin();
    $second = salesAdmin();

    Livewire::actingAs($first)
        ->test('pages::admin.sales')
        ->call('setCurrency', 'ugx')
        ->assertSet('currency', 'ugx');

    expect($first->fresh()->billing_currency)->toBe('ugx')
        ->and($second->fresh()->billing_currency)->toBeNull();

    Livewire::actingAs($second)
        ->test('pages::admin.sales')
        ->assertSet('currency', 'usd');
});

test('the admin payments page shows the currency switcher', function () {
    $admin = salesAdmin();

    Livewire::actingAs($admin)
        ->test('pages::admin.payments')
        ->assertOk()
        ->assertSee('USD')
        ->assertSee('UGX')
        ->call('setCurrency', 'ugx')
        ->assertSet('currency', 'ugx');
});

test('the sales report PDF includes the period purpose and method breakdowns', function () {
    $admin = salesAdmin();
    $payment = Payment::factory()->paid()->create([
        'user_id' => $admin->id,
        'amount' => 50,
        'currency' => 'USD',
        'payment_method' => PaymentMethod::Bank,
    ]);

    $this->view('pdf.sales-report', [
        'payments' => collect([$payment]),
        'revenue' => 50.0,
        'refunds' => 0.0,
        'byPurpose' => collect([$payment->purpose->label() => ['count' => 1, 'amount' => 50.0]]),
        'byMethod' => collect([$payment->payment_method->label() => ['count' => 1, 'amount' => 50.0]]),
        'period' => 'month',
        'currency' => 'usd',
        'admin' => $admin,
    ])->assertSee('Revenue breakdown')
        ->assertSee('By purpose')
        ->assertSee('By method')
        ->assertSee($payment->purpose->label())
        ->assertSee('Bank Transfer')
        ->assertSee('images/logo-black-400.png')
        ->assertSee('50.00 USD')
        ->assertSee('proodev.com | Aletheia Uganda Software Company Limited')
        ->assertSee('Kampala - Uganda')
        ->assertSee('Tel: +256 786 634 306')
        ->assertSee('sales@proodev.com')
        ->assertSee('Tax ID UG 1016550521');
});

test('an admin can export the sales report as a PDF', function () {
    $admin = salesAdmin();
    Payment::factory()->paid()->create(['user_id' => $admin->id, 'amount' => 50, 'currency' => 'USD']);

    $response = $this->actingAs($admin)
        ->get(route('admin.sales.export.pdf', ['period' => 'month']));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="sales-report-'.now()->format('Y-m-d').'.pdf"');

    expect($response->content())->toStartWith('%PDF')
        ->and(strlen($response->content()))->toBeGreaterThan(1000);
});

test('non-admins are forbidden from the sales report PDF', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.sales.export.pdf'))
        ->assertForbidden();
});
