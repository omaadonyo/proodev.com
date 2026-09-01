<?php

use App\Enums\PaymentMethod;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Mail\NewUserRegisteredMail;
use App\Mail\PaymentInvoiceMail;
use App\Mail\PayoutNotificationMail;
use App\Mail\WelcomeMail;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\NewUserRegisteredNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\WelcomeNotification;
use App\Services\BillingService;
use App\Services\NotificationService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

test('the welcome email and notification are sent to a new user on registration', function () {
    Mail::fake();
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);

    $user = User::factory()->create();

    Event::dispatch(new Registered($user));

    Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail) => $mail->hasTo($user->email));
    Mail::assertSent(NewUserRegisteredMail::class, fn (NewUserRegisteredMail $mail) => $mail->hasTo($admin->email));

    Notification::assertSentTo($user, WelcomeNotification::class);
    Notification::assertSentTo($admin, NewUserRegisteredNotification::class);
});

test('confirming a payment sends an invoice to the customer and a copy to admins', function () {
    Mail::fake();
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'status' => PaymentStatus::Pending,
    ]);

    app(BillingService::class)->markPaid($payment);

    Mail::assertQueued(PaymentInvoiceMail::class, fn (PaymentInvoiceMail $mail) => $mail->hasTo($user->email) && $mail->copy === false);
    Mail::assertQueued(PaymentInvoiceMail::class, fn (PaymentInvoiceMail $mail) => $mail->hasTo($admin->email) && $mail->copy === true);
    Notification::assertSentTo($admin, PaymentReceivedNotification::class);
});

test('confirming a payment records when the receipt was emailed to the buyer', function () {
    Mail::fake();
    User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'status' => PaymentStatus::Pending,
    ]);

    app(BillingService::class)->markPaid($payment);

    $payment->refresh();

    expect($payment->invoiceEmailed())->toBeTrue()
        ->and($payment->invoice_emailed_at)->not->toBeNull();
});

test('a worldremit payment sends a receipt to the buyer and a payout notice to admins', function () {
    Mail::fake();
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'status' => PaymentStatus::Pending,
        'payment_method' => PaymentMethod::WorldRemit,
    ]);

    app(BillingService::class)->markPaid($payment);

    Mail::assertQueued(PaymentInvoiceMail::class, fn (PaymentInvoiceMail $mail) => $mail->hasTo($user->email) && $mail->copy === false);
    Mail::assertQueued(PayoutNotificationMail::class, fn (PayoutNotificationMail $mail) => $mail->hasTo($admin->email));
    Mail::assertNotQueued(PaymentInvoiceMail::class, fn (PaymentInvoiceMail $mail) => $mail->hasTo($admin->email));
    Notification::assertSentTo($admin, PaymentReceivedNotification::class);
});

test('a bank transfer payment sends a payout notice to admins', function () {
    Mail::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Verification,
        'status' => PaymentStatus::Pending,
        'payment_method' => PaymentMethod::Bank,
    ]);

    app(BillingService::class)->markPaid($payment);

    Mail::assertQueued(PayoutNotificationMail::class, fn (PayoutNotificationMail $mail) => $mail->hasTo($admin->email));
});

test('the payout mail renders the mobile money payout details', function () {
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'status' => PaymentStatus::Paid,
        'payment_method' => PaymentMethod::WorldRemit,
        'gateway_reference' => 'WRM-ABCD1234-42',
    ]);

    $html = (new PayoutNotificationMail($payment))->render();

    expect($html)->toContain('Payout needed')
        ->toContain('Pay to')
        ->toContain('Uganda')
        ->toContain('MTN Mobile Money')
        ->toContain('0786634306')
        ->toContain('Emmanuel Adonyo')
        ->toContain('WRM-ABCD1234-42')
        ->toContain('Open sales panel');
});

test('the payout mail renders bank payout details', function () {
    config()->set('payments.methods.bank.account_name', 'ProoDev Ltd');
    config()->set('payments.methods.bank.account_number', '1234567890');
    config()->set('payments.methods.bank.bank_name', 'Test Bank');

    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'status' => PaymentStatus::Paid,
        'payment_method' => PaymentMethod::Bank,
    ]);

    $html = (new PayoutNotificationMail($payment))->render();

    expect($html)->toContain('Payout details')
        ->toContain('Test Bank')
        ->toContain('ProoDev Ltd')
        ->toContain('1234567890');
});

test('the notification service does not notify when a payment is already paid', function () {
    Mail::fake();
    Notification::fake();
    $user = User::factory()->create();
    $payment = Payment::factory()->create([
        'user_id' => $user->id,
        'purpose' => PaymentPurpose::Credits,
        'status' => PaymentStatus::Paid,
    ]);

    app(BillingService::class)->markPaid($payment);

    Mail::assertNothingQueued();
    Notification::assertNothingSent();
});

test('the invoice mail renders for every payment purpose', function () {
    $user = User::factory()->create();

    foreach ([PaymentPurpose::Verification, PaymentPurpose::Credits, PaymentPurpose::Subscription] as $purpose) {
        $payment = Payment::factory()->create([
            'user_id' => $user->id,
            'purpose' => $purpose,
            'status' => PaymentStatus::Paid,
        ]);

        (new PaymentInvoiceMail($payment))->render();
    }

    expect(true)->toBeTrue();
});

test('the welcome mail renders', function () {
    $user = User::factory()->create();

    (new WelcomeMail($user))->render();

    expect(true)->toBeTrue();
});

test('the new-user mail renders for admins', function () {
    $user = User::factory()->create();

    (new NewUserRegisteredMail($user))->render();

    expect(true)->toBeTrue();
});

test('database notifications are stored for the recipient', function () {
    Notification::fake();
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create();

    app(NotificationService::class)->newRegistration($user);

    Notification::assertSentTo($user, WelcomeNotification::class, 1);
    Notification::assertSentTo($admin, NewUserRegisteredNotification::class, 1);
});
