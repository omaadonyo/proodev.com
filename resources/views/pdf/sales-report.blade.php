<?php

use App\Support\BillingCurrency;

$seller = config('billing.seller');
$count = $payments->count();
$avg = $count > 0 ? $payments->avg(fn ($p) => (float) $p->amount) : 0;
$periodLabel = match ($period) {
    'today' => 'Today',
    'week' => 'This week',
    'month' => 'This month',
    'quarter' => 'This quarter',
    'year' => 'This year',
    default => 'All time',
};
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
            <div class="doc-label">Sales report</div>
            <div class="doc-title">{{ $periodLabel }}</div>
            <div class="doc-sub">Generated {{ now()->format('M j, Y g:i A') }} · Prepared by {{ $admin->name }} · Amounts in {{ strtoupper($currency) }}</div>
        </td>
    </tr></table>

    <table class="summary"><tr>
        <td><div class="box"><div class="label">Revenue</div><div class="value">{{ $money($revenue) }}</div><span class="sub">Top purpose: {{ $byPurpose->keys()->first() ?? '—' }}</span></div></td>
        <td><div class="box"><div class="label">Transactions</div><div class="value">{{ number_format($count) }}</div><span class="sub">Top method: {{ $byMethod->keys()->first() ?? '—' }}</span></div></td>
        <td><div class="box"><div class="label">Average order</div><div class="value">{{ $money($avg) }}</div><span class="sub">Per transaction</span></div></td>
        <td><div class="box"><div class="label">Refunded</div><div class="value">{{ $money($refunds) }}</div><span class="sub">Within period</span></div></td>
    </tr></table>

    <div class="section">Revenue breakdown</div>

    @if ($byPurpose->isEmpty() && $byMethod->isEmpty())
        <div class="empty">No paid transactions in this period.</div>
    @else
        <table style="width: 100%; border-collapse: collapse;"><tr>
            <td style="width: 50%; vertical-align: top; padding-right: 5mm;">
                <table class="items">
                    <thead>
                        <tr>
                            <th>By purpose</th>
                            <th class="amt">Tx</th>
                            <th class="amt">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byPurpose as $label => $row)
                            <tr @class(['alt' => $loop->even])>
                                <td>{{ $label }}</td>
                                <td class="amt">{{ number_format($row['count']) }}</td>
                                <td class="amt">{{ $money($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="color: #9ca3af;">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top;">
                <table class="items">
                    <thead>
                        <tr>
                            <th>By method</th>
                            <th class="amt">Tx</th>
                            <th class="amt">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byMethod as $label => $row)
                            <tr @class(['alt' => $loop->even])>
                                <td>{{ $label }}</td>
                                <td class="amt">{{ number_format($row['count']) }}</td>
                                <td class="amt">{{ $money($row['amount']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" style="color: #9ca3af;">None</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </td>
        </tr></table>
    @endif

    <div class="section">Transactions ({{ $count }})</div>

    @if ($payments->isEmpty())
        <div class="empty">No paid transactions in this period.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 24mm;">Invoice</th>
                    <th>Customer</th>
                    <th>Purpose</th>
                    <th>Method</th>
                    <th class="amt" style="width: 30mm;">Amount</th>
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
