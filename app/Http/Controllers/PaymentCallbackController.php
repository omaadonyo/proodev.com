<?php

namespace App\Http\Controllers;

use App\Enums\PaymentPurpose;
use App\Models\Payment;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

class PaymentCallbackController extends Controller
{
    /**
     * Webhook-style notification endpoint (CSRF-exempt) hit by gateways.
     */
    public function notify(Request $request, Payment $payment, PaymentProcessor $processor)
    {
        $confirmed = $processor->handleNotification($payment, $request->all());

        return response()->json(['status' => $confirmed ? 'ok' : 'ignored']);
    }

    /**
     * Hosted checkout page used when a gateway is not fully configured
     * (local simulation) or as a safe landing page for bank transfer.
     */
    public function checkout(Request $request, Payment $payment)
    {
        abort_unless($payment->status->value === 'pending', 404);

        return view('payments.checkout', ['payment' => $payment]);
    }

    /**
     * Simulated gateway confirmation used during local development.
     */
    public function simulate(Request $request, Payment $payment, PaymentProcessor $processor)
    {
        $method = (string) $payment->payment_method?->value;
        $payload = $method === 'pesapal'
            ? [
                'OrderNotificationType' => 'IPNCHANGE',
                'OrderTrackingId' => (string) Str::uuid(),
                'OrderMerchantReference' => (string) $payment->gateway_reference,
                'payment_status_code' => '1',
            ]
            : [
                'data' => [
                    'status' => 'successful',
                    'tx_ref' => (string) $payment->gateway_reference,
                ],
            ];

        $processor->handleNotification($payment, $payload);

        return redirect()->to($this->returnUrl($payment))->with('payment', 'confirmed');
    }

    private function returnUrl(Payment $payment): string
    {
        return match ($payment->purpose) {
            PaymentPurpose::Verification => route('verify'),
            PaymentPurpose::Subscription, PaymentPurpose::JobPosts => $payment->company
                ? route('companies.manage', $payment->company)
                : route('subscription'),
            default => route('credits'),
        };
    }
}
