<?php

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected from the billing page to login', function () {
    $this->get(route('billing'))->assertRedirect(route('login'));
});

test('the billing page lists all of a users payments with invoice actions', function () {
    $user = User::factory()->create(['name' => 'Jane Dev']);
    $verification = Payment::factory()->verification()->paid()->create([
        'user_id' => $user->id,
        'amount' => 8,
        'currency' => 'USD',
    ]);
    $credits = Payment::factory()->paid()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'amount' => 24,
        'currency' => 'USD',
        'metadata' => ['credits' => 32],
    ]);

    Livewire::actingAs($user)
        ->test('pages::billing')
        ->assertOk()
        ->assertSee('Billing history')
        ->assertSee($verification->invoiceNumber())
        ->assertSee($credits->invoiceNumber())
        ->assertSee('Verification')
        ->assertSee('Credit Purchase')
        ->assertSee('Download')
        ->assertSeeHtml('action="'.route('invoices.email', $verification).'"')
        ->assertSee('proodev.com | Aletheia Uganda Software Company Limited')
        ->assertSee('Tel: +256 786 634 306')
        ->assertSee('Tax ID UG 1016550521');
});

test('the billing page includes company subscription payments for owned companies', function () {
    $owner = User::factory()->create(['name' => 'Acme Owner']);
    $company = Company::factory()->create(['owner_id' => $owner->id, 'name' => 'Acme Corp']);

    $payment = Payment::factory()->subscription()->paid()->create([
        'user_id' => $owner->id,
        'company_id' => $company->id,
        'amount' => 499,
        'currency' => 'USD',
        'metadata' => ['plan' => 'intelligence'],
    ]);

    Livewire::actingAs($owner)
        ->test('pages::billing')
        ->assertOk()
        ->assertSee($payment->invoiceNumber())
        ->assertSee('Company Subscription');
});

test('pending payments show awaiting confirmation without invoice actions', function () {
    $user = User::factory()->create();

    Payment::factory()->create(['user_id' => $user->id, 'amount' => 8, 'currency' => 'USD']);

    Livewire::actingAs($user)
        ->test('pages::billing')
        ->assertOk()
        ->assertSee('Awaiting confirmation')
        ->assertDontSee('Download');
});

test('customer-confirmed pending payments show the verifying pill', function () {
    $user = User::factory()->create();

    Payment::factory()->create([
        'user_id' => $user->id,
        'status' => PaymentStatus::Pending,
        'customer_confirmed_at' => now()->subMinutes(10),
    ]);

    Livewire::actingAs($user)
        ->test('pages::billing')
        ->assertOk()
        ->assertSee("We're verifying your payment", false);
});

test('plain pending payments do not show the verifying pill', function () {
    $user = User::factory()->create();

    Payment::factory()->create([
        'user_id' => $user->id,
        'status' => PaymentStatus::Pending,
        'customer_confirmed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test('pages::billing')
        ->assertOk()
        ->assertDontSee("We're verifying your payment", false);
});

test('other users payments never appear on the billing page', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    $payment = Payment::factory()->paid()->create(['user_id' => $stranger->id, 'amount' => 999, 'currency' => 'USD']);

    Livewire::actingAs($user)
        ->test('pages::billing')
        ->assertOk()
        ->assertDontSee($payment->invoiceNumber());
});

test('the billing history can be exported as a CSV file', function () {
    $user = User::factory()->create(['name' => 'Jane Dev']);
    $payment = Payment::factory()->verification()->paid()->create([
        'user_id' => $user->id,
        'amount' => 8,
        'currency' => 'USD',
    ]);

    $this->actingAs($user)
        ->get(route('billing.export.csv'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="billing-history-'.now()->format('Y-m-d').'.csv"')
        ->assertSee($payment->invoiceNumber())
        ->assertSee('Developer Verification Badge');
});

test('the billing history can be exported as a PDF file', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->paid()->create([
        'user_id' => $user->id,
        'amount' => 24,
        'currency' => 'USD',
    ]);

    $response = $this->actingAs($user)->get(route('billing.export.pdf'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf')
        ->assertHeader('Content-Disposition', 'attachment; filename="billing-history-'.now()->format('Y-m-d').'.pdf"');

    expect($response->content())->toStartWith('%PDF')
        ->and(strlen($response->content()))->toBeGreaterThan(1000)
        ->and(str_contains($response->content(), '/Image'))->toBeTrue();
});

test('the billing history PDF document carries the brand logo', function () {
    $user = User::factory()->create();
    Payment::factory()->paid()->create(['user_id' => $user->id, 'amount' => 24, 'currency' => 'USD']);

    $this->view('pdf.billing-history', ['user' => $user, 'payments' => $user->payments()->get()])
        ->assertSee('images/logo-black-400.png');
});

test('guests are redirected from the billing exports to login', function () {
    $this->get(route('billing.export.csv'))->assertRedirect(route('login'));
    $this->get(route('billing.export.pdf'))->assertRedirect(route('login'));
});

test('billing exports never include other users payments', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();

    Payment::factory()->paid()->create(['user_id' => $stranger->id, 'amount' => 999, 'currency' => 'USD']);

    $this->actingAs($user)
        ->get(route('billing.export.csv'))
        ->assertOk()
        ->assertDontSee('INV-');
});

test('an admin can export the full payment ledger as CSV', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $customer = User::factory()->create(['name' => 'Ledger Customer']);

    $payment = Payment::factory()->paid()->create([
        'user_id' => $customer->id,
        'amount' => 499,
        'currency' => 'USD',
        'purpose' => PaymentPurpose::Subscription,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sales.export'))
        ->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
        ->assertHeader('Content-Disposition', 'attachment; filename="platform-ledger-'.now()->format('Y-m-d').'.csv"')
        ->assertSee($payment->invoiceNumber())
        ->assertSee('Ledger Customer')
        ->assertSee('Company Subscription');
});

test('non-admins are forbidden from the ledger export', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.sales.export'))
        ->assertForbidden();
});
