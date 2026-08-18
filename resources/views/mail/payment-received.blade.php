<x-mail.layout :subject="'Payment received — we\'re verifying it ('.$payment->invoiceNumber().')'" docLabel="PAYMENT RECEIVED">
    <h1>Payment received — we're on it</h1>
    <p class="lead">
        Thanks {{ $payment->billedTo()['name'] }} — we've recorded your
        <strong>{{ $method?->label() ?? 'manual transfer' }}</strong> submission for
        <strong>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</strong>
        and are verifying it now.
    </p>

    <div class="card">
        <div class="card-title">{{ $payment->purpose->label() }}</div>
        <div class="grid" style="margin-bottom: 0;">
            <div class="col">
                <div class="label">Reference</div>
                <div class="value"><strong>{{ $payment->invoiceNumber() }}</strong></div>
            </div>
            <div class="col">
                <div class="label">Amount</div>
                <div class="value"><strong>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</strong></div>
            </div>
            <div class="col">
                <div class="label">Submitted</div>
                <div class="value"><strong>{{ $submittedAt->format('M j, Y g:i A') }}</strong></div>
            </div>
        </div>
    </div>

    <h2>What happens next</h2>
    <div class="steps">
        <div class="step">
            <div class="step-num">STEP 1</div>
            <div class="step-title">We verify the transfer</div>
            <p>Our team checks the funds arrived.</p>
        </div>
        <div class="step">
            <div class="step-num">STEP 2</div>
            <div class="step-title">Payment confirmed</div>
            <p>Your purchase is fulfilled automatically.</p>
        </div>
        <div class="step">
            <div class="step-num">STEP 3</div>
            <div class="step-title">Receipt emailed</div>
            <p>Your official invoice arrives by email.</p>
        </div>
    </div>

    <p class="muted" style="font-size: 12px;">
        This is an acknowledgment only — not a receipt. Track the status anytime from your
        <a href="{{ route('billing') }}" style="color: #4f46e5;">billing history</a>.
    </p>

    <div class="btn-row">
        <a class="btn" href="{{ route('billing') }}">View billing history</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Not expecting this? You can
        <a href="{{ route('profile.edit') }}" style="color: #4f46e5;">adjust your email preferences</a>.
    </p>
</x-mail.layout>
