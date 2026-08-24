<x-mail.layout subject="Pay your hiring verification invoice" docLabel="ACTION REQUIRED">
    <h1>Your job post is almost live</h1>
    <p class="lead">
        An invoice of <strong style="color:#1a202c">${{ number_format((float) $payment->amount, 0) }} {{ $payment->currency }}</strong>
        is waiting for payment. Once confirmed, your company is verified and your job post publishes automatically.
    </p>

    <div class="grid">
        <div class="col">
            <div class="value"><strong>Invoice #{{ $payment->id }}</strong></div>
            <p class="muted" style="font-size: 13px;">Hiring verification — {{ $payment->company?->name }}</p>
        </div>
    </div>

    <div class="btn-row">
        <a class="btn" href="{{ route('checkout', $payment) }}">Pay invoice — ${{ number_format((float) $payment->amount, 0) }}</a>
    </div>

    <div class="divider"></div>

    <ul class="muted" style="font-size: 12px; padding-left: 18px;">
        <li>Verification unlocks chat, hiring tools and your full recruiter pipeline.</li>
        <li>Your held job post goes live the moment the invoice is approved.</li>
    </ul>
</x-mail.layout>