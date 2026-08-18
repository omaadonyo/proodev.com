<?php

use App\Support\BillingCurrency;

$seller = config('billing.seller');
$count = $payments->count();
$money = fn (float $amount) => BillingCurrency::format($amount, $currency);
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
            <div class="doc-label">Export</div>
            <div class="doc-title">Selected transactions</div>
            <div class="doc-sub">{{ $count }} transaction{{ $count !== 1 ? 's' : '' }} · Generated {{ now()->format('M j, Y g:i A') }} · Amounts in {{ strtoupper($currency) }}</div>
        </td>
    </tr></table>

    <table class="summary"><tr>
        <td><div class="box"><div class="label">Transactions</div><div class="value">{{ number_format($count) }}</div></div></td>
        <td><div class="box"><div class="label">Total amount</div><div class="value">{{ $money($total) }}</div></div></td>
    </tr></table>

    <div class="section">Transactions</div>

    @if ($payments->isEmpty())
        <div class="empty">No transactions selected.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 24mm;">Invoice</th>
                    <th>Customer</th>
                    <th>Purpose</th>
                    <th>Method</th>
                    <th class="amt" style="width: 30mm;">Amount</th>
                    <th>Status</th>
                    <th style="width: 24mm;">Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payments as $payment)
                    <tr @class(['alt' => $loop->even])>
                        <td class="mono">{{ $payment->invoiceNumber() }}</td>
                        <td>{{ $payment->company?->name ?? $payment->user?->name ?? '—' }}</td>
                        <td>{{ $payment->purpose->label() }}</td>
                        <td>{{ $payment->payment_method?->label() ?? 'Manual' }}</td>
                        <td class="amt">{{ $money((float) $payment->amount) }}</td>
                        <td>
                            <span class="badge {{ $payment->status->value }}">{{ $payment->status->label() }}</span>
                        </td>
                        <td>{{ ($payment->paid_at ?? $payment->created_at)->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="totals">
        <tr class="grand">
            <td>Total ({{ $count }} transaction{{ $count !== 1 ? 's' : '' }})</td>
            <td class="amt">{{ $money($total) }}</td>
        </tr>
    </table>

    <div class="page-footer">
        <span class="brand-line">{{ $seller['name'] }}</span> · {{ $seller['email'] }} · {{ $seller['phone'] }}<br>
        <span class="page-num"></span>
    </div>
</body>
</html>
