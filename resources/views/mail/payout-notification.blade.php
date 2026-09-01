@php
    $settings = $method instanceof \App\Enums\PaymentMethod
        ? app(\App\Services\Payments\PaymentMethodSettings::class)->for($method)
        : [];
    $manual = $method instanceof \App\Enums\PaymentMethod && $method->isManual();
@endphp

<x-mail.layout :subject="'Payout needed, '.$payment->purpose->label()" docLabel="PAYOUT NOTICE">
    <h1>Manual payment confirmed, payout needed</h1>
    <p class="lead">
        A payment via <strong>{{ $method?->label() ?? 'manual transfer' }}</strong> was marked paid.
        Settle the payout to the details below.
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
            <div class="label">Status</div>
            <div class="value">
                <span class="badge paid">{{ $payment->status->label() }}</span><br>
                @if ($method)
                    <span class="muted">Paid via {{ $method->label() }}</span>
                @endif
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
        <h2>Payout details: {{ $method->label() }}</h2>
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
        @if ($payment->paid_at)
            · Confirmed {{ $payment->paid_at->diffForHumans() }}
        @endif
    </p>

    <div class="btn-row">
        <a class="btn" href="{{ route('admin.sales') }}">Open sales panel</a>
    </div>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        The buyer already received their receipt, no further action needed once the payout is sent.
    </p>
</x-mail.layout>
