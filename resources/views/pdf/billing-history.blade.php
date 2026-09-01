<?php

use App\Enums\PaymentStatus;

$seller = config('billing.seller');
$total = $payments->where('status', PaymentStatus::Paid)->sum(fn ($p) => (float) $p->amount);
$refunded = $payments->where('status', PaymentStatus::Refunded)->sum(fn ($p) => (float) $p->amount);
$currency = (string) config('billing.currency', 'USD');
$money = fn (float $amount, ?string $code = null) => number_format($amount, 2, '.', ',').' '.($code ?? $currency);
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
            <div class="seller">{{ str_replace(['https://', 'http://'], '', $seller['website']) }} | {{ $seller['name'] }}<br>{{ $seller['address'] }}, {{ $seller['city'] }} - {{ $seller['country'] }}<br>Tel: {{ $seller['phone'] }} | {{ $seller['email'] }} | Tax ID {{ $seller['tax_id'] }}</div>
        </td>
        <td style="vertical-align: middle;">
            <div class="doc-label">Billing history</div>
            <div class="doc-title">{{ $user->name }}</div>
            <div class="doc-sub">Generated {{ now()->format('M j, Y g:i A') }} · {{ $payments->count() }} payments</div>
        </td>
    </tr></table>

    <table class="meta"><tr>
        <td style="width: 40%;">
            <div class="label">Prepared for</div>
            <div class="value"><strong>{{ $user->name }}</strong></div>
            <div class="muted">{{ $user->email }}</div>
        </td>
        <td>
            <div class="label">Account</div>
            <div class="value"><strong>#{{ $user->id }}</strong></div>
            <div class="muted">{{ $user->handle() }}</div>
        </td>
        <td>
            <div class="label">Currency</div>
            <div class="value"><strong>{{ $currency }}</strong></div>
            <div class="muted">All amounts in {{ $currency }}</div>
        </td>
    </tr></table>

    <table class="summary"><tr>
        <td><div class="box"><div class="label">Total paid</div><div class="value">{{ $money($total) }}</div><span class="sub">Across all confirmed payments</span></div></td>
        <td><div class="box"><div class="label">Confirmed</div><div class="value">{{ $payments->where('status', PaymentStatus::Paid)->count() }}</div><span class="sub">Paid transactions</span></div></td>
        <td><div class="box"><div class="label">Pending</div><div class="value">{{ $payments->where('status', PaymentStatus::Pending)->count() }}</div><span class="sub">Awaiting confirmation</span></div></td>
        <td><div class="box"><div class="label">Refunded</div><div class="value">{{ $money($refunded) }}</div><span class="sub">Within this history</span></div></td>
    </tr></table>

    <div class="section">Transactions ({{ $payments->count() }})</div>

    @if ($payments->isEmpty())
        <div class="empty">No payments recorded for this account yet.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 24mm;">Invoice</th>
                    <th>Purpose</th>
                    <th>Method</th>
                    <th class="amt" style="width: 26mm;">Amount</th>
                    <th style="width: 18mm;">Status</th>
                    <th style="width: 24mm;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr @class(['alt' => $loop->even])>
                        <td class="mono">{{ $payment->invoiceNumber() }}</td>
                        <td>{{ $payment->purpose->label() }}</td>
                        <td>{{ $payment->payment_method?->label() ?? 'Manual' }}</td>
                        <td class="amt">{{ $money((float) $payment->amount, $payment->currency) }}</td>
                        <td><span class="badge {{ $payment->status->value }}">{{ $payment->status->label() }}</span></td>
                        <td>{{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-footer">
        <span class="brand-line">{{ $seller['name'] }}</span> · {{ $seller['email'] }} · {{ $seller['phone'] }}<br>
        <span class="page-num"></span>
    </div>
</body>
</html>
