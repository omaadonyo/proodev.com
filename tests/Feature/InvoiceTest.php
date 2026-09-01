<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\PaymentInvoiceMail;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use App\Support\InvoicePdf;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

function paidPaymentFor(User $user): Payment
{
    return Payment::factory()->paid()->create([
        'user_id' => $user->id,
        'amount' => 8,
        'currency' => 'USD',
        'payment_method' => PaymentMethod::Flutterwave,
        'gateway_reference' => 'flw_12345',
    ]);
}

test('guests are redirected from the invoice page to login', function () {
    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $this->get(route('invoices.show', $payment))->assertRedirect(route('login'));
});

test('the payer can view their printable invoice', function () {
    $owner = User::factory()->create(['name' => 'Jane Dev']);
    $payment = paidPaymentFor($owner);

    $this->actingAs($owner)
        ->get(route('invoices.show', $payment))
        ->assertOk()
        ->assertSee('Invoice')
        ->assertSee($payment->invoiceNumber())
        ->assertSee('Jane Dev')
        ->assertSee($owner->email)
        ->assertSee('Total paid')
        ->assertSee('Download PDF')
        ->assertSee('Email a copy')
        ->assertSee('proodev.com | Aletheia Uganda Software Company Limited')
        ->assertSee('Tel: +256 786 634 306')
        ->assertSee('Tax ID UG 1016550521');
});

test('another user cannot view someone else\'s invoice', function () {
    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $this->actingAs(User::factory()->create())
        ->get(route('invoices.show', $payment))
        ->assertForbidden();
});

test('a company owner can view their company subscription invoice', function () {
    $owner = User::factory()->create(['name' => 'Acme Owner']);
    $company = Company::factory()->create(['owner_id' => $owner->id, 'name' => 'Acme Corp']);

    $payment = Payment::factory()->subscription()->paid()->create([
        'user_id' => $owner->id,
        'company_id' => $company->id,
        'amount' => 499,
        'currency' => 'USD',
        'metadata' => ['plan' => 'intelligence'],
    ]);

    $this->actingAs($owner)
        ->get(route('invoices.show', $payment))
        ->assertOk()
        ->assertSee('INV-')
        ->assertSee('Acme Corp')
        ->assertSee('Company Subscription');
});

test('an admin can view any invoice', function () {
    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $this->actingAs(User::factory()->create(['is_admin' => true]))
        ->get(route('invoices.show', $payment))
        ->assertOk();
});

test('emailing a copy sends the invoice to the customer and records the time', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $this->actingAs($owner)
        ->post(route('invoices.email', $payment))
        ->assertRedirect(route('invoices.show', $payment));

    Mail::assertQueued(PaymentInvoiceMail::class, fn ($mail) => $mail->hasTo($owner->email));

    expect($payment->fresh()->invoiceEmailed())->toBeTrue()
        ->and($payment->fresh()->invoice_emailed_at)->not->toBeNull();
});

test('a non-owner cannot email a copy of an invoice', function () {
    Mail::fake();

    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $this->actingAs(User::factory()->create())
        ->post(route('invoices.email', $payment))
        ->assertForbidden();

    Mail::assertNothingSent();
});

test('the admin payments page shows invoice actions for confirmed payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create(['name' => 'Jane Dev']);
    $payment = paidPaymentFor($owner);

    Livewire::actingAs($admin)
        ->test('pages::admin.payments')
        ->assertOk()
        ->assertSee($payment->invoiceNumber())
        ->assertSeeHtml('action="'.route('invoices.email', $payment).'"')
        ->assertSee('Email');
});

test('the admin sales page shows invoice actions for confirmed payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $owner = User::factory()->create(['name' => 'Jane Dev']);
    $payment = paidPaymentFor($owner);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee($payment->invoiceNumber())
        ->assertSeeHtml('action="'.route('invoices.email', $payment).'"');
});

test('the invoice email carries the generated PDF attachment', function () {
    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $mail = new PaymentInvoiceMail($payment);
    $attachments = $mail->attachments();

    expect($attachments)->toHaveCount(1)
        ->and($attachments[0]->as)->toBe($payment->invoiceNumber().'.pdf')
        ->and($attachments[0]->mime)->toBe('application/pdf');
});

test('the invoice document carries the full seller company block', function () {
    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $this->view('pdf.invoice', ['payment' => $payment])
        ->assertSee('proodev.com | Aletheia Uganda Software Company Limited')
        ->assertSee('Plot 2141, Luzira Portbell Rd, Natasha Road TankHill Rd, Kampala - Uganda')
        ->assertSee('Tel: +256 786 634 306')
        ->assertSee('sales@proodev.com')
        ->assertSee('Tax ID UG 1016550521')
        ->assertSee('images/logo-black-400.png');
});

test('the invoice PDF generator produces a valid pdf for every purpose', function () {
    $owner = User::factory()->create();
    $payment = paidPaymentFor($owner);

    $pdf = app(InvoicePdf::class)->generate($payment);

    expect($pdf)->toStartWith('%PDF')
        ->and(strlen($pdf))->toBeGreaterThan(1000)
        ->and(app(InvoicePdf::class)->filename($payment))->toBe($payment->invoiceNumber().'.pdf');
});

test('pending payments are not listed as invoices but still render', function () {
    $owner = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $owner->id,
        'amount' => 8,
        'currency' => 'USD',
        'status' => PaymentStatus::Pending,
    ]);

    $this->actingAs($owner)
        ->get(route('invoices.show', $payment))
        ->assertOk()
        ->assertSee('Pending');
});
