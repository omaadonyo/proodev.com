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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $payment->purpose->label() }} · Invoice {{ $payment->invoiceNumber() }} · {{ config('app.name', 'ProoDev') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #eef0f3;
            color: #1f2430;
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
            padding: 28px 16px 48px;
        }

        .sheet {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 24px 64px rgba(15, 23, 42, 0.12);
            border: 1px solid #e5e7eb;
        }
        .accent-bar { height: 6px; background: linear-gradient(90deg, #4f46e5, #7c6cff 55%, #4f46e5); }

        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: flex-end;
            max-width: 820px;
            margin: 0 auto 14px;
        }
        .toolbar .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 11px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            font-family: inherit;
        }
        .btn-primary { background: #111827; color: #ffffff; box-shadow: 0 4px 12px rgba(17, 24, 39, 0.18); }
        .btn-primary:hover { background: #1f2937; }
        .btn-secondary { background: #ffffff; color: #374151; border-color: #e5e7eb; }
        .btn-secondary:hover { border-color: #cbd5e1; background: #f9fafb; }

        .header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 24px;
            padding: 40px 48px 28px;
            border-bottom: 1px solid #f1f2f4;
        }
        .brand { display: inline-flex; align-items: center; gap: 12px; }
        .brand-mark {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #6d5ef2);
            color: #ffffff;
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.02em;
            box-shadow: 0 6px 14px rgba(79, 70, 229, 0.28);
        }
        .brand-name { color: #111827; font-size: 20px; font-weight: 800; letter-spacing: -0.02em; }
        .brand-name span { color: #4f46e5; }
        .doc-head { text-align: right; }
        .doc-label {
            display: inline-block;
            color: #4f46e5;
            background: #eef2ff;
            border: 1px solid #e0e7ff;
            padding: 6px 14px;
            font-size: 11px;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            font-weight: 700;
            border-radius: 999px;
        }
        .doc-title { font-size: 26px; font-weight: 800; color: #111827; letter-spacing: -0.02em; margin-top: 12px; }
        .doc-sub { font-size: 13px; color: #6b7280; margin-top: 2px; }

        .meta-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 20px;
            padding: 28px 48px 30px;
        }
        .label { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.09em; font-weight: 700; margin-bottom: 6px; }
        .value { font-size: 14px; color: #1f2430; }
        .value strong { font-weight: 700; color: #111827; }
        .muted { color: #6b7280; font-size: 13px; }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .badge.paid { background: #ecfdf5; color: #059669; }
        .badge.pending { background: #fffbeb; color: #b45309; }
        .badge.refunded { background: #fef2f2; color: #b91c1c; }
        .badge.cancelled { background: #f3f4f6; color: #6b7280; }

        .body { padding: 6px 48px 34px; }
        .table-head { font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.09em; font-weight: 700; padding-bottom: 10px; border-bottom: 2px solid #111827; }

        table.items { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 14px; }
        table.items th {
            text-align: left;
            padding: 10px 12px;
            background: #f9fafb;
            color: #6b7280;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.09em;
            border-bottom: 1px solid #eceef1;
        }
        table.items th:first-child, table.items td:first-child { padding-left: 0; }
        table.items th:last-child, table.items td:last-child { text-align: right; padding-right: 0; }
        table.items td { padding: 14px 12px; border-bottom: 1px solid #f3f4f6; color: #374151; vertical-align: top; }
        table.items td:first-child { padding-left: 0; }
        table.items td:last-child { padding-right: 0; font-weight: 600; color: #111827; white-space: nowrap; }
        .item-desc { font-size: 13px; color: #6b7280; margin-top: 3px; max-width: 460px; }

        .totals { width: 100%; border-collapse: collapse; margin-top: 22px; }
        .totals td { padding: 7px 0; font-size: 14px; color: #4b5563; }
        .totals td:last-child { text-align: right; font-weight: 600; color: #1f2430; font-variant-numeric: tabular-nums; }
        .totals tr.grand td { border-top: 2px solid #111827; padding-top: 13px; font-size: 17px; font-weight: 800; color: #111827; }

        .payment-box {
            margin-top: 26px;
            border: 1px solid #eef0f3;
            background: #fafbfc;
            border-radius: 12px;
            padding: 16px 18px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
        }
        .payment-box .kv { font-size: 12px; }
        .payment-box .kv .label { margin-bottom: 3px; }
        .payment-box .kv .value { font-size: 13px; font-weight: 600; color: #111827; word-break: break-all; }

        .notes { margin-top: 26px; font-size: 13px; color: #4b5563; }
        .notes strong { color: #111827; }
        .flash {
            margin-top: 14px;
            padding: 12px 16px;
            border-radius: 10px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            font-size: 14px;
            font-weight: 600;
        }

        .footer {
            padding: 22px 48px 26px;
            border-top: 1px solid #f1f2f4;
            background: #fafbfc;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
        }
        .footer .brand-line { color: #6b7280; font-weight: 700; margin-bottom: 5px; }
        .footer a { color: #4f46e5; text-decoration: none; }

        @media (max-width: 640px) {
            .header, .meta-grid, .body { padding-left: 24px; padding-right: 24px; }
            .footer { padding-left: 24px; padding-right: 24px; }
            .meta-grid { grid-template-columns: 1fr; gap: 14px; }
            .doc-head { text-align: left; }
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .sheet { border: none; border-radius: 0; box-shadow: none; max-width: none; }
            .toolbar { display: none; }
            .flash { display: none; }
            .accent-bar { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            @page { size: A4; margin: 14mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" class="btn btn-secondary" onclick="window.print()">⬇&nbsp; Download PDF</button>
        <form method="POST" action="{{ route('invoices.email', $payment) }}" style="display: inline-flex;">
            @csrf
            <button type="submit" class="btn btn-primary">✉&nbsp; Email a copy</button>
        </form>
        <a class="btn btn-secondary" href="{{ url()->previous() === url()->current() ? route('credits') : url()->previous() }}">←&nbsp; Back</a>
    </div>

    @if ($sent)
        <div class="flash" style="max-width: 820px; margin: 0 auto 14px;">Invoice emailed. A copy is on its way to {{ $billedTo['email'] }}.</div>
    @endif

    <div class="sheet">
        <div class="accent-bar"></div>

        <div class="header">
            <div class="brand">
                <img src="{{ asset('images/logo-black.png') }}" alt="ProoDev" style="height: 36px; width: auto; display: block;" />
                <div class="seller" style="font-size: 11px; color: #6b7280; line-height: 1.5;">
                    {{ str_replace(['https://', 'http://'], '', $seller['website']) }} | {{ $seller['name'] }}<br>
                    {{ $seller['address'] }}, {{ $seller['city'] }} - {{ $seller['country'] }}<br>
                    Tel: {{ $seller['phone'] }} | {{ $seller['email'] }} | Tax ID {{ $seller['tax_id'] }}
                </div>
            </div>
            <div class="doc-head">
                <span class="doc-label">Invoice</span>
                <div class="doc-title">{{ $payment->invoiceNumber() }}</div>
                <div class="doc-sub">{{ $payment->purpose->label() }}</div>
            </div>
        </div>

        <div class="meta-grid">
            <div>
                <div class="label">Billed to</div>
                <div class="value">
                    <strong>{{ $billedTo['name'] }}</strong>
                    @if ($billedTo['company'] && $billedTo['company'] !== $billedTo['name'])
                        <div class="muted">{{ $billedTo['company'] }}</div>
                    @endif
                    <div class="muted">{{ $billedTo['email'] }}</div>
                </div>
            </div>
            <div>
                <div class="label">Issue date</div>
                <div class="value"><strong>{{ $issuedAt->format('M j, Y') }}</strong></div>
                @if ($payment->paid_at)
                    <div class="muted">Paid {{ $payment->paid_at->format('M j, Y g:i A') }}</div>
                @endif
            </div>
            <div>
                <div class="label">Status</div>
                <div class="value">
                    <span class="badge {{ $payment->status->value }}">{{ $payment->status->label() }}</span>
                    @if ($payment->payment_method)
                        <div class="muted" style="margin-top: 6px;">Paid via {{ $payment->payment_method->label() }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="body">
            <table class="items">
                <thead>
                    <tr>
                        <th>Description</th>
                        <th style="width: 140px;">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ $payment->lineDescription() }}
                            <div class="item-desc">
                                {{ $payment->purpose->label() }} · {{ config('app.name', 'ProoDev') }} ·
                                @if ($payment->purpose->value === 'subscription')
                                    {{ \App\Enums\CompanyPlan::tryFrom((string) ($payment->metadata['plan'] ?? ''))?->label() ?? 'Company plan' }}
                                @else
                                    {{ $payment->purpose->label() }}
                                @endif
                            </div>
                        </td>
                        <td>{{ $amount }} {{ $payment->currency }}</td>
                    </tr>
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td>Subtotal</td>
                    <td>{{ $amount }} {{ $payment->currency }}</td>
                </tr>
                <tr>
                    <td>Tax (0%)</td>
                    <td>0.00 {{ $payment->currency }}</td>
                </tr>
                <tr class="grand">
                    <td>Total paid</td>
                    <td>{{ $amount }} {{ $payment->currency }}</td>
                </tr>
            </table>

            <div class="payment-box">
                <div class="kv">
                    <div class="label">Payment reference</div>
                    <div class="value">#{{ $payment->id }}</div>
                </div>
                @if ($payment->uuid)
                    <div class="kv">
                        <div class="label">Payment UUID</div>
                        <div class="value">{{ $payment->uuid }}</div>
                    </div>
                @endif
                @if ($payment->gateway_reference)
                    <div class="kv">
                        <div class="label">Gateway reference</div>
                        <div class="value">{{ $payment->gateway_reference }}</div>
                    </div>
                @endif
                @if ($payment->provider)
                    <div class="kv">
                        <div class="label">Provider</div>
                        <div class="value">{{ ucfirst((string) $payment->provider) }}</div>
                    </div>
                @endif
            </div>

            <div class="notes">
                <strong>Thank you.</strong>
                @if ($payment->status === \App\Enums\PaymentStatus::Paid)
                    Your payment has been confirmed and applied.
                @else
                    This invoice will update automatically once the payment is confirmed.
                @endif
                For questions about this invoice, contact
                <a href="mailto:{{ $seller['email'] }}">{{ $seller['email'] }}</a>.
            </div>
        </div>

        <div class="footer">
            <div class="brand-line">{{ str_replace(['https://', 'http://'], '', $seller['website']) }} | {{ $seller['name'] }}</div>
            {{ $seller['address'] }}, {{ $seller['city'] }} - {{ $seller['country'] }}<br>
            Tel: {{ $seller['phone'] }} · <a href="mailto:{{ $seller['email'] }}">{{ $seller['email'] }}</a> · Tax ID {{ $seller['tax_id'] }}
        </div>
    </div>
</body>
</html>
