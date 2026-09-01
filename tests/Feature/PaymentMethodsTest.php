<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\PaymentAwaitingConfirmationMail;
use App\Mail\PaymentInvoiceMail;
use App\Mail\PaymentReceivedMail;
use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentAwaitingConfirmationNotification;
use App\Services\BillingService;
use App\Services\Payments\PaymentMethodSettings;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function checkoutUser(): User
{
    return User::factory()->create();
}

test('enabled payment methods reflect settings', function () {
    $settings = app(PaymentMethodSettings::class);

    expect($settings->isEnabled(PaymentMethod::Bank))->toBeTrue()
        ->and($settings->isEnabled(PaymentMethod::Flutterwave))->toBeTrue()
        ->and($settings->isEnabled(PaymentMethod::Pesapal))->toBeTrue()
        ->and($settings->isEnabled(PaymentMethod::WorldRemit))->toBeTrue()
        ->and($settings->enabledMethods())->toContain(PaymentMethod::Bank)
        ->and($settings->enabledMethods())->toContain(PaymentMethod::WorldRemit);
});

test('unconfigured methods stay hidden until their details are added', function () {
    $settings = app(PaymentMethodSettings::class);

    expect($settings->isConfigured(PaymentMethod::Bank))->toBeFalse()
        ->and($settings->isConfigured(PaymentMethod::Pesapal))->toBeFalse()
        ->and($settings->isConfigured(PaymentMethod::Flutterwave))->toBeFalse()
        ->and($settings->isConfigured(PaymentMethod::WorldRemit))->toBeTrue()
        ->and($settings->usableMethods())->toBe([PaymentMethod::WorldRemit]);

    config()->set('payments.methods.flutterwave.public_key', 'pk_test');
    config()->set('payments.methods.flutterwave.secret_key', 'sk_test');

    expect($settings->usableMethods())->toBe([PaymentMethod::Flutterwave, PaymentMethod::WorldRemit]);
});

test('a disabled payment method cannot be used for checkout', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    $settings = app(PaymentMethodSettings::class);
    $settings->update(PaymentMethod::Bank, false, []);

    $threw = false;

    try {
        app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Bank);
    } catch (InvalidArgumentException) {
        $threw = true;
    }

    expect($threw)->toBeTrue()
        ->and($payment->fresh()->payment_method)->toBeNull();

    $settings->update(PaymentMethod::Bank, true, []);
});

test('initiating checkout stamps the payment with the selected method', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    $payment->refresh();

    expect($payment->payment_method)->toBe(PaymentMethod::Flutterwave)
        ->and($payment->provider)->toBe('flutterwave')
        ->and($payment->gateway_reference)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending);
});

test('checkout URLs use the payment uuid and references are six alpha characters', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    expect($payment->uuid)->not->toBeNull()
        ->and(parse_url(route('checkout', $payment), PHP_URL_PATH))->toBe('/checkout/'.$payment->uuid)
        ->and(parse_url(route('checkout', $payment), PHP_URL_PATH))->not->toBe('/checkout/'.$payment->id);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Bank);

    $payment->refresh();

    expect($payment->gateway_reference)->toMatch('/^[A-Z]{6}$/');
});

test('bank transfer returns payment instructions and a reference', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    $initiation = app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Bank);

    $payment->refresh();

    expect($initiation->redirects())->toBeFalse()
        ->and($initiation->instructions)->not->toBeEmpty()
        ->and($initiation->instructions['reference'])->toBe($payment->gateway_reference)
        ->and($payment->gateway_reference)->not->toBeNull();
});

test('worldremit returns mobile money instructions with the MTN account', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    $initiation = app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    $payment->refresh();

    expect($initiation->redirects())->toBeFalse()
        ->and($initiation->instructions)->not->toBeEmpty()
        ->and($initiation->instructions['pay_to'])->toBe('Uganda')
        ->and($initiation->instructions['mobile_money_provider'])->toBe('MTN Mobile Money')
        ->and($initiation->instructions['mobile_money_number'])->toBe('0786634306')
        ->and($initiation->instructions['account_name'])->toBe('Emmanuel Adonyo')
        ->and($initiation->instructions['reference'])->toBe($payment->gateway_reference);
});

test('an unconfigured gateway returns the local simulated checkout', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    $initiation = app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    expect($initiation->redirects())->toBeTrue()
        ->and($initiation->redirectUrl)->toContain('payments/'.$payment->uuid.'/checkout');
});

test('a gateway notification marks the payment paid and fulfils credits', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    $confirmed = app(PaymentProcessor::class)->handleNotification($payment, [
        'data' => [
            'status' => 'successful',
            'tx_ref' => $payment->gateway_reference,
        ],
    ]);

    $user->refresh();

    expect($confirmed)->toBeTrue()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->paid_at)->not->toBeNull()
        ->and($payment->fresh()->gateway_data)->not->toBeNull()
        ->and($user->creditBalance())->toBe(8);
});

test('a failed gateway notification is ignored', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Pesapal);

    $confirmed = app(PaymentProcessor::class)->handleNotification($payment, [
        'payment_status' => 'declined',
        'transaction_reference' => $payment->gateway_reference,
    ]);

    expect($confirmed)->toBeFalse()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('a mismatched gateway reference is ignored', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    $confirmed = app(PaymentProcessor::class)->handleNotification($payment, [
        'data' => [
            'status' => 'successful',
            'tx_ref' => 'SOME-OTHER-REF',
        ],
    ]);

    expect($confirmed)->toBeFalse()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending);
});

test('pesapal notification confirms with a completed status', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Pesapal);

    $confirmed = app(PaymentProcessor::class)->handleNotification($payment, [
        'OrderNotificationType' => 'IPNCHANGE',
        'OrderTrackingId' => 'b945e4af-80a5-4ec1-8706-e03f8332fb04',
        'OrderMerchantReference' => $payment->gateway_reference,
        'payment_status_code' => '1',
    ]);

    expect($confirmed)->toBeTrue()
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('the notify endpoint confirms a flutterwave-style payment without csrf', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    $this->postJson(route('payments.notify', $payment), [
        'data' => [
            'status' => 'successful',
            'tx_ref' => $payment->gateway_reference,
        ],
    ])->assertOk()->assertJson(['status' => 'ok']);

    $user->refresh();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($user->creditBalance())->toBe(8);
});

test('the checkout page requires the payment owner', function () {
    $user = checkoutUser();
    $other = User::factory()->create();
    $payment = Payment::factory()->create(['user_id' => $other->id]);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertStatus(403);
});

test('the checkout page redirects paid payments to the receipt', function () {
    $user = checkoutUser();
    $payment = Payment::factory()->create(['user_id' => $user->id, 'status' => PaymentStatus::Paid]);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertRedirect(route('invoices.show', $payment));
});

test('the checkout page redirects refunded payments to the receipt', function () {
    $user = checkoutUser();
    $payment = Payment::factory()->create(['user_id' => $user->id, 'status' => PaymentStatus::Refunded]);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertRedirect(route('invoices.show', $payment));
});

test('confirming after the admin marked the payment paid redirects to the receipt', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    $tester = Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit');

    // The admin confirms the transfer while the customer still has the page open.
    $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now()]);

    $tester
        ->call('confirmPayment')
        ->assertRedirect(route('invoices.show', $payment));
});

test('the checkout page offers only configured methods with flutterwave first', function () {
    config()->set('payments.methods.flutterwave.public_key', 'pk_test');
    config()->set('payments.methods.flutterwave.secret_key', 'sk_test');

    Http::fake([
        'https://api.flutterwave.com/*' => Http::response([
            'status' => 'success',
            'data' => ['link' => 'https://checkout.flutterwave.com/pay/xyz'],
        ]),
    ]);

    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertOk()
        ->assertSee('Flutterwave')
        ->assertSee('WorldRemit')
        ->assertDontSee('Bank Transfer')
        ->assertDontSee('Pesapal')
        ->assertSee('Pay instantly with Visa, Mastercard')
        ->assertSeeInOrder(['Flutterwave', 'WorldRemit'])
        ->assertSee('images/payments/flutterwave.png')
        ->assertSee('images/payments/worldremit.png')
        ->call('selectMethod', 'flutterwave')
        ->assertSet('payment.payment_method', PaymentMethod::Flutterwave)
        ->assertSet('redirectUrl', 'https://checkout.flutterwave.com/pay/xyz')
        ->assertSee('Continue to Flutterwave');
});

test('unconfigured gateways are not offered and never show simulate at checkout', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertOk()
        ->assertSee('WorldRemit')
        ->assertDontSee('Bank Transfer')
        ->assertDontSee('Pesapal')
        ->assertDontSee('Flutterwave')
        ->assertDontSee('Simulated checkout');
});

test('bank transfer appears once account details are added and shows instructions', function () {
    config()->set('payments.methods.bank.account_name', 'ProoDev Ltd');
    config()->set('payments.methods.bank.account_number', '1234567890');

    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertSee('Bank Transfer')
        ->call('selectMethod', 'bank')
        ->assertSet('payment.payment_method', PaymentMethod::Bank)
        ->assertSee('Bank Transfer instructions')
        ->assertSee($payment->fresh()->gateway_reference);
});

test('the checkout page reassures buyers that payment details are never stored', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->assertOk()
        ->assertSee('Secure checkout')
        ->assertSee('never stores your credit card or payment details');
});

test('the checkout page shows worldremit MTN mobile money instructions', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->assertSet('payment.payment_method', PaymentMethod::WorldRemit)
        ->assertSee('WorldRemit instructions')
        ->assertSee('Pay To')
        ->assertSee('Uganda')
        ->assertSee('MTN Mobile Money')
        ->assertSee('0786634306')
        ->assertSee('Emmanuel Adonyo')
        ->assertSee($payment->fresh()->gateway_reference);
});

test('the checkout page redirects to a gateway when configured', function () {
    config()->set('payments.methods.flutterwave.public_key', 'pk_test');
    config()->set('payments.methods.flutterwave.secret_key', 'sk_test');

    Http::fake([
        'https://api.flutterwave.com/*' => Http::response([
            'status' => 'success',
            'data' => ['link' => 'https://checkout.flutterwave.com/pay/xyz'],
        ]),
    ]);

    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'flutterwave')
        ->assertSet('payment.payment_method', PaymentMethod::Flutterwave)
        ->assertSet('redirectUrl', 'https://checkout.flutterwave.com/pay/xyz')
        ->assertSee('Continue to Flutterwave');
});

test('the simulate endpoint confirms the payment and redirects to credits', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::Flutterwave);

    $this->actingAs($user)
        ->get(route('payments.simulate', $payment))
        ->assertRedirect(route('credits'));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('the admin sales page lists transactions', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = checkoutUser();
    Payment::factory()->create(['user_id' => $user->id, 'purpose' => 'credits', 'status' => PaymentStatus::Paid]);

    $this->actingAs($admin)
        ->get(route('admin.sales'))
        ->assertOk()
        ->assertSee('Sales')
        ->assertSee('All transactions')
        ->assertSee($user->name);
});

test('the admin sales page can confirm a pending payment', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $payment = Payment::factory()->verification()->create();

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('markPaid', $payment->id)
        ->assertHasNoErrors();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid);
});

test('an admin can update payment method settings', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('editMethod', 'bank')
        ->assertSet('methodSettings.account_number', '')
        ->set('methodSettings.account_number', '1234567890')
        ->call('saveMethod')
        ->assertHasNoErrors();

    $settings = app(PaymentMethodSettings::class)->for(PaymentMethod::Bank);

    expect($settings['account_number'])->toBe('1234567890');
});

test('admins can disable a payment method from the sales page', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->call('editMethod', 'pesapal')
        ->call('toggleMethod')
        ->call('saveMethod')
        ->assertHasNoErrors();

    expect(app(PaymentMethodSettings::class)->isEnabled(PaymentMethod::Pesapal))->toBeFalse();
});

test('buying credits redirects to the shared checkout page', function () {
    $user = checkoutUser();

    Livewire::actingAs($user)
        ->test('pages::credits')
        ->call('purchase', 0)
        ->assertRedirect(route('checkout', Payment::where('user_id', $user->id)->latest()->first()));
});

test('buying verification redirects to the shared checkout page', function () {
    $user = checkoutUser();

    Livewire::actingAs($user)
        ->test('pages::verify')
        ->set('shortName', 'sam-dev')
        ->call('purchase')
        ->assertRedirect(route('checkout', Payment::where('user_id', $user->id)->latest()->first()));
});

test('upgrading a company subscription redirects to the shared checkout page', function () {
    $owner = User::factory()->company()->create();
    $company = Company::factory()->create(['owner_id' => $owner->id]);

    Livewire::actingAs($owner)
        ->test('pages::subscription')
        ->call('upgrade', 'recruiter')
        ->assertRedirect(route('checkout', Payment::where('company_id', $company->id)->latest()->first()));
});

test('the guest checkout page is protected', function () {
    $user = checkoutUser();
    $payment = Payment::factory()->create(['user_id' => $user->id]);

    $this->get(route('checkout', $payment))->assertRedirect(route('login'));
});

test('a customer can confirm a worldremit payment and admins are alerted', function () {
    Mail::fake();
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->call('confirmPayment')
        ->assertHasNoErrors();

    $payment->refresh();

    expect($payment->confirmedByCustomer())->toBeTrue()
        ->and($payment->customer_confirmed_at)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending);

    Notification::assertSentTo($admin, PaymentAwaitingConfirmationNotification::class);
    Mail::assertQueued(PaymentAwaitingConfirmationMail::class);
    Mail::assertQueued(PaymentReceivedMail::class);
    Mail::assertNotQueued(PaymentInvoiceMail::class);
});

test('a buyer who opted out of transactional emails skips the acknowledgment', function () {
    Mail::fake();
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $user = checkoutUser();
    $user->forceFill(['preferences' => array_merge($user->preferences ?? [], ['email_transactions' => false])])->save();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->call('confirmPayment')
        ->assertHasNoErrors();

    Mail::assertNotQueued(PaymentReceivedMail::class, fn (PaymentReceivedMail $mail) => $mail->hasTo($user->email));

    // The admin alert is independent of the buyer's preferences.
    Notification::assertSentTo($admin, PaymentAwaitingConfirmationNotification::class);
    Mail::assertQueued(PaymentAwaitingConfirmationMail::class);
});

test('the buyer receives the payment-received acknowledgment on confirm', function () {
    Mail::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->call('confirmPayment')
        ->assertHasNoErrors();

    Mail::assertQueued(
        PaymentReceivedMail::class,
        fn (PaymentReceivedMail $mail) => $mail->hasTo($user->email)
            && $mail->payment->is($payment)
    );

    // The acknowledgment is lightweight — never the invoice.
    Mail::assertNotQueued(PaymentInvoiceMail::class);
});

test('confirming a manual payment twice does not duplicate admin alerts', function () {
    Mail::fake();
    Notification::fake();

    $admin = User::factory()->create(['is_admin' => true]);
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->call('confirmPayment')
        ->call('confirmPayment')
        ->assertHasNoErrors();

    Notification::assertSentToTimes($admin, PaymentAwaitingConfirmationNotification::class, 1);
    Mail::assertQueued(PaymentAwaitingConfirmationMail::class, 1);
    Mail::assertQueued(PaymentReceivedMail::class, 1);
});

test('the worldremit checkout keeps the confirm and pay-later invoice actions separate', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->assertSee('sent the payment — confirm', false)
        ->assertSee('no invoice is generated by confirming')
        ->assertSee('Create a pending invoice')
        ->assertSeeHtml(route('invoices.show', $payment));
});

test('the checkout shows the submitted state after confirming', function () {
    $user = checkoutUser();
    $payment = app(BillingService::class)->createCreditPayment($user, 0);
    app(PaymentProcessor::class)->initiate($payment, PaymentMethod::WorldRemit);

    Livewire::actingAs($user)
        ->test('pages::checkout', ['payment' => $payment])
        ->call('selectMethod', 'worldremit')
        ->call('confirmPayment')
        ->assertSee('Payment submitted')
        ->assertSee('awaiting admin confirmation')
        ->assertDontSee("I've sent the payment — confirm");
});

test('the admin sales page flags pending payments confirmed by the customer', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $payment = Payment::factory()->create([
        'status' => PaymentStatus::Pending,
        'customer_confirmed_at' => now(),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee('Customer confirmed')
        ->assertSee('No invoice generated yet');
});

test('the pending list does not show the no-invoice notice for plain pending payments', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Payment::factory()->create([
        'status' => PaymentStatus::Pending,
        'customer_confirmed_at' => null,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertDontSee('No invoice generated yet');
});

test('the sales row shows when the receipt was emailed to the buyer', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Payment::factory()->create([
        'status' => PaymentStatus::Paid,
        'invoice_emailed_at' => now()->subMinutes(5),
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertSee('Receipt emailed');
});

test('the sales row omits the receipt time before the receipt has been emailed', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    Payment::factory()->create([
        'status' => PaymentStatus::Paid,
        'invoice_emailed_at' => null,
    ]);

    Livewire::actingAs($admin)
        ->test('pages::admin.sales')
        ->assertOk()
        ->assertDontSee('Receipt emailed');
});
