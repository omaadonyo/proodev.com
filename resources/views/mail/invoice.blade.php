<x-mail.layout :subject="$payment->purpose->label().' '.($copy ? '— Admin copy' : '— Receipt')" docLabel="INVOICE">
    <h1>Invoice &amp; Receipt</h1>
    <p class="lead">Your official invoice and receipt — a PDF copy is attached.</p>

    <div class="grid">
        <div class="col">
            <div class="label">Billed to</div>
            <div class="value">
                <strong>{{ $payment->billedTo()['name'] }}</strong><br>
                <span class="muted">{{ $payment->billedTo()['email'] }}</span>
            </div>
        </div>
        <div class="col">
            <div class="label">Invoice</div>
            <div class="value"><strong>{{ $payment->invoiceNumber() }}</strong><br>
                <span class="muted">Issued {{ $payment->paid_at?->toFormattedDateString() ?? now()->toFormattedDateString() }}</span>
            </div>
        </div>
        <div class="col">
            <div class="label">Status</div>
            <div class="value">
                <span class="badge {{ $payment->status->value === 'paid' ? 'paid' : 'pending' }}">{{ $payment->status->label() }}</span><br>
                @if ($payment->payment_method)
                    <span class="muted">Paid via {{ $payment->payment_method->label() }}</span>
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

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
        </tr>
        <tr>
            <td>Tax (0%)</td>
            <td>0.00 {{ $payment->currency }}</td>
        </tr>
        <tr class="total">
            <td>Total paid</td>
            <td>{{ number_format((float) $payment->amount, 2) }} {{ $payment->currency }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <p class="muted" style="font-size: 12px;">
        Payment reference <strong style="color:#1a202c">#{{ $payment->id }}</strong>
        @if ($payment->gateway_reference)
            · Gateway ref <strong style="color:#1a202c">{{ $payment->gateway_reference }}</strong>
        @endif
        @if ($payment->paid_at)
            · Paid {{ $payment->paid_at->diffForHumans() }}
        @endif
    </p>

    <p class="muted" style="font-size: 12px; margin-top: 12px;">
        Questions? Contact {{ config('billing.seller.email') }}.
    </p>
</x-mail.layout>
