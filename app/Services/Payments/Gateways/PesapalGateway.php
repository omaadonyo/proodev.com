<?php

namespace App\Services\Payments\Gateways;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;

class PesapalGateway extends AbstractGateway
{
    public function method(): PaymentMethod
    {
        return PaymentMethod::Pesapal;
    }

    protected function isConfigured(array $settings): bool
    {
        return filled($settings['consumer_key'] ?? null)
            && filled($settings['consumer_secret'] ?? null)
            && filled($settings['ipn_id'] ?? null);
    }

    protected function buildRedirect(Payment $payment, array $settings): string
    {
        $baseUrl = rtrim((string) $settings['base_url'], '/');

        $token = $this->accessToken($baseUrl, $settings);

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($baseUrl.'/api/Transactions/SubmitOrderRequest', [
                'id' => (string) $payment->gateway_reference,
                'currency' => (string) ($settings['currency'] ?? $payment->currency),
                'amount' => (float) $payment->amount,
                'description' => $payment->purpose->label().' #'.$payment->id,
                'callback_url' => $this->returnUrl($payment),
                'notification_id' => (string) $settings['ipn_id'],
                'billing_address' => [
                    'email_address' => $payment->user->email,
                    'first_name' => $payment->user->name,
                    'last_name' => '',
                    'country_code' => '',
                ],
            ]);

        $url = data_get($response->json(), 'redirect_url');

        if (! $url) {
            throw new \RuntimeException('Pesapal failed to create an order request.');
        }

        return $url;
    }

    public function verifyNotification(Payment $payment, array $payload): bool
    {
        $reference = (string) data_get(
            $payload,
            'OrderMerchantReference',
            data_get($payload, 'transaction_reference', ''),
        );

        $statusCode = (string) data_get($payload, 'payment_status_code', '');
        $statusDescription = strtolower((string) data_get($payload, 'payment_status_description', ''));
        $status = strtolower((string) data_get($payload, 'payment_status', ''));

        $isCompleted = $statusCode === '1'
            || in_array($statusDescription, ['completed', 'success', 'paid'], true)
            || in_array($status, ['completed', 'success', 'paid'], true);

        return $isCompleted && $reference === (string) $payment->gateway_reference;
    }

    private function accessToken(string $baseUrl, array $settings): string
    {
        $response = Http::acceptJson()
            ->post($baseUrl.'/api/Auth/RequestToken', [
                'consumer_key' => $settings['consumer_key'],
                'consumer_secret' => $settings['consumer_secret'],
            ]);

        $token = data_get($response->json(), 'token');

        if (! $token) {
            throw new \RuntimeException('Pesapal authentication failed.');
        }

        return $token;
    }
}
