<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class FlutterwaveGateway extends AbstractGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Flutterwave;
    }

    protected function isConfigured(array $settings): bool
    {
        return filled($settings['secret_key'] ?? null)
            && filled($settings['public_key'] ?? null);
    }

    protected function buildRedirect(Payment $payment, array $settings): string
    {
        try {
            $response = Http::withToken((string) $settings['secret_key'])
                ->acceptJson()
                ->post(rtrim((string) $settings['base_url'], '/').'/payments', [
                    'tx_ref' => (string) $payment->gateway_reference,
                    'amount' => (float) $payment->amount,
                    'currency' => (string) ($settings['currency'] ?? $payment->currency),
                    'redirect_url' => $this->returnUrl($payment),
                    'customer' => [
                        'email' => $payment->user->email,
                        'name' => $payment->user->name,
                    ],
                    'customizations' => [
                        'title' => $payment->purpose->label(),
                    ],
                ]);

            $link = data_get($response->json(), 'data.link');

            if ($link) {
                return $link;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        // Fallback to simulated checkout when gateway is not reachable or keys are dummy
        return route('payments.checkout', $payment);
    }

    public function verifyNotification(Payment $payment, array $payload): bool
    {
        $status = strtolower((string) data_get($payload, 'data.status', ''));
        $txRef = (string) data_get($payload, 'data.tx_ref', '');

        return $status === 'successful'
            && $txRef === (string) $payment->gateway_reference;
    }
}
