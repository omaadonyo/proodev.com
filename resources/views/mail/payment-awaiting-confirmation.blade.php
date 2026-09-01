@php
    $settings = $method instanceof \App\Enums\PaymentMethod
        ? app(\App\Services\Payments\PaymentMethodSettings::class)->for($method)
        : [];
    $manual = $method instanceof \App\Enums\PaymentMethod && $method->isManual();
@endphp

<x-mail.layout :subject="'Payment submitted, '.$payment->purpose->label()" docLabel="PAYMENT SUBMITTED">
    <h1>Customer submitted a manual payment</h1>
    <p class="lead">
        A customer says they sent a payment via <strong>{{ $method?->label() ?? 'manual transfer' }}</strong>.
        Verify the funds, then confirm it in the sales panel.
    </p>

    <div class="grid">
        <div class="col">
            <div class="label">Customer</div>
            <div class="value">
                <strong>{{ $payment->billedTo()['name'] }}</strong><br>
                <span class="muted">{{ $payment->billedTo()['email'] }}</span>
            </div>
        </div>
        <div class="col">
            <div class="label">Payment</div>
            <div class="value"><strong>{{ $payment->invoiceNumber() }}</strong><br>
                <span class="muted">{{ $payment->purpose->label() }}</span>
            </div>
        </div>
        <div class="col">
            <div class="label">Submitted</div>
            <div class="value"><strong>{{ $submittedAt->format('M j, Y g:i A') }}</strong><br>
                <span class="muted">Status · pending</span>
            </div>
        </div>
    </div>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $payment->lineDescription() }}</td>
                <td>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
            </tr>
        </tbody>
    </table>

    @if ($manual)
        <h2>Where the funds should arrive: {{ $method->label() }}</h2>
        <div class="card">
            <div class="card-title">{{ $method === \App\Enums\PaymentMethod::WorldRemit ? 'WorldRemit → Mobile money' : 'Bank transfer' }}</div>
            @if ($method === \App\Enums\PaymentMethod::WorldRemit)
                <div class="value" style="margin-top: 6px;">
                    <span class="muted">Pay to · </span><strong>{{ $settings['payout_country'] ?? 'Uganda' }}</strong><br>
                    <strong>{{ $settings['mobile_money_provider'] ?? 'MTN Mobile Money' }}</strong><br>
                    <span class="muted">Number · </span><strong>{{ $settings['mobile_money_number'] ?? '-' }}</strong><br>
                    <span class="muted">Account name · </span><strong>{{ $settings['account_name'] ?? '-' }}</strong>
                </div>
            @else
                <div class="value" style="margin-top: 6px;">
                    @if (! empty($settings['bank_name']))
                        <strong>{{ $settings['bank_name'] }}</strong><br>
                    @endif
                    <span class="muted">Account name · </span><strong>{{ $settings['account_name'] ?? '-' }}</strong><br>
                    <span class="muted">Account number · </span><strong>{{ $settings['account_number'] ?? '-' }}</strong>
                    @if (! empty($settings['bank_code']))
                        <br><span class="muted">Bank code · </span><strong>{{ $settings['bank_code'] }}</strong>
                    @endif
                </div>
            @endif
        </div>
    @endif

    <p class="muted" style="font-size: 12px;">
        Payment reference <strong style="color:#1a202c">#{{ $payment->id }}</strong>
        @if ($payment->gateway_reference)
            · Gateway ref <strong style="color:#1a202c">{{ $payment->gateway_reference }}</strong>
        @endif
        · Customer reference to match: <strong style="color:#1a202c">{{ $payment->gateway_reference }}</strong>
    </p>

    <div class="btn-row">
        <a class="btn" href="{{ route('admin.sales') }}">Open sales panel</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Match the reference in your statement, then confirm the payment to send the customer their receipt.
    </p>
</x-mail.layout>
