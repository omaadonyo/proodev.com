<?php

$seller = config('billing.seller');
$billedTo = $payment->billedTo();
$amount = number_format((float) $payment->amount, 2, '.', ',');
$issuedAt = $payment->paid_at ?? $payment->created_at;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    @include('pdf._styles')
</head>
<body>
    <table class="header"><tr>
        <td style="vertical-align: middle;">
            <img src="{{ public_path('images/logo-black-400.png') }}" alt="ProoDev" class="brand-logo" />
            <div class="seller">
                {{ str_replace(['https://', 'http://'], '', $seller['website']) }} | {{ $seller['name'] }}<br>
                {{ $seller['address'] }}, {{ $seller['city'] }} - {{ $seller['country'] }}<br>
                Tel: {{ $seller['phone'] }} | {{ $seller['email'] }} | Tax ID {{ $seller['tax_id'] }}
            </div>
        </td>
        <td style="vertical-align: middle;">
            <div class="doc-label">Invoice</div>
            <div class="doc-title">{{ $payment->invoiceNumber() }}</div>
            <div class="doc-sub">{{ $payment->purpose->label() }} · {{ config('app.name', 'ProoDev') }}</div>
        </td>
    </tr></table>

    <table class="meta"><tr>
        <td style="width: 50%;">
            <div class="label">Billed to</div>
            <div class="value"><strong>{{ $billedTo['name'] }}</strong></div>
            @if ($billedTo['company'] && $billedTo['company'] !== $billedTo['name'])
                <div class="muted">{{ $billedTo['company'] }}</div>
            @endif
            <div class="muted">{{ $billedTo['email'] }}</div>
        </td>
        <td style="width: 25%;">
            <div class="label">Issue date</div>
            <div class="value"><strong>{{ $issuedAt->format('M j, Y') }}</strong></div>
            @if ($payment->paid_at)
                <div class="muted">Paid {{ $payment->paid_at->format('M j, Y g:i A') }}</div>
            @endif
        </td>
        <td style="width: 25%;">
            <div class="label">Status</div>
            <div class="value"><span class="badge {{ $payment->status->value }}">{{ $payment->status->label() }}</span></div>
            @if ($payment->payment_method)
                <div class="muted">via {{ $payment->payment_method->label() }}</div>
            @endif
        </td>
    </tr></table>

    <div class="section">Invoice items</div>

    <table class="items">
        <thead>
            <tr>
                <th>Description</th>
                <th class="amt" style="width: 45mm;">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    {{ $payment->lineDescription() }}
                    <div class="muted" style="margin-top: 1mm;">
                        {{ $payment->purpose->label() }} · {{ config('app.name', 'ProoDev') }} ·
                        @if ($payment->purpose->value === 'subscription')
                            {{ \App\Enums\CompanyPlan::tryFrom((string) ($payment->metadata['plan'] ?? ''))?->label() ?? 'Company plan' }}
                        @else
                            {{ $payment->purpose->label() }}
                        @endif
                    </div>
                </td>
                <td class="amt">{{ $amount }} {{ $payment->currency }}</td>
            </tr>
        </tbody>
    </table>

    <table style="width: 100%;"><tr>
        <td style="width: 50%; vertical-align: bottom;">
            <table class="info-box">
                <tr>
                    <td style="width: 33%;">
                        <div class="label">Payment reference</div>
                        <div class="value">#{{ $payment->id }}</div>
                    </td>
                    @if ($payment->gateway_reference)
                        <td style="width: 33%;">
                            <div class="label">Gateway reference</div>
                            <div class="value">{{ $payment->gateway_reference }}</div>
                        </td>
                    @endif
                    @if ($payment->provider)
                        <td style="width: 33%;">
                            <div class="label">Provider</div>
                            <div class="value">{{ ucfirst((string) $payment->provider) }}</div>
                        </td>
                    @endif
                </tr>
            </table>
        </td>
        <td style="width: 50%; vertical-align: bottom; padding-left: 6mm;">
            <table class="totals">
                <tr><td>Subtotal</td><td class="amt">{{ $amount }} {{ $payment->currency }}</td></tr>
                <tr><td>Tax (0%)</td><td class="amt">0.00 {{ $payment->currency }}</td></tr>
                <tr class="grand"><td>Total paid</td><td class="amt">{{ $amount }} {{ $payment->currency }}</td></tr>
            </table>
        </td>
    </tr></table>

    <div class="notes">
        <strong>Thank you.</strong>
        @if ($payment->status === \App\Enums\PaymentStatus::Paid)
            Your payment has been confirmed and applied.
        @else
            This invoice will update automatically once the payment is confirmed.
        @endif
        For questions about this invoice, contact {{ $seller['email'] }}.
    </div>

    <div class="page-footer">
        <span class="brand-line">{{ $seller['name'] }}</span> · {{ $seller['email'] }} · {{ $seller['phone'] }}<br>
        <span class="page-num"></span>
    </div>
</body>
</html>
