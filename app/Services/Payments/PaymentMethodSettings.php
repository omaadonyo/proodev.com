<?php

namespace App\Services\Payments;

use App\Enums\PaymentMethod;
use App\Models\PaymentMethodSetting;

/**
 * Reads and writes per-method settings. Admin-stored values take precedence
 * over the static defaults in config/payments.php.
 */
class PaymentMethodSettings
{
    /**
     * Get the merged settings for a method (DB overrides + config defaults).
     *
     * @return array<string, mixed>
     */
    public function for(PaymentMethod $method): array
    {
        $defaults = (array) config("payments.methods.{$method->value}", []);
        $stored = $this->row($method)?->settings ?? [];

        return array_merge($defaults, $stored);
    }

    public function isEnabled(PaymentMethod $method): bool
    {
        return (bool) ($this->row($method)?->enabled ?? (bool) config("payments.methods.{$method->value}.enabled", true));
    }

    /**
     * Whether the method has the details needed to actually take payment.
     * Bank needs account details, Pesapal needs its consumer keys, Flutterwave
     * needs its public/secret keys and WorldRemit needs the mobile-money
     * number it pays out to. Until these are present the method stays hidden
     * at checkout, so customers never hit the simulated fallback.
     */
    public function isConfigured(PaymentMethod $method): bool
    {
        $settings = $this->for($method);

        return match ($method) {
            PaymentMethod::Bank => filled($settings['account_name'] ?? null)
                && filled($settings['account_number'] ?? null),
            PaymentMethod::Pesapal => filled($settings['consumer_key'] ?? null)
                && filled($settings['consumer_secret'] ?? null),
            PaymentMethod::Flutterwave => filled($settings['public_key'] ?? null)
                && filled($settings['secret_key'] ?? null),
            PaymentMethod::WorldRemit => filled($settings['mobile_money_number'] ?? null),
        };
    }

    /**
     * The order methods are presented in: Flutterwave first, then WorldRemit,
     * with Bank and Pesapal at the back while they remain unconfigured.
     */
    public function displayOrder(PaymentMethod $method): int
    {
        return match ($method) {
            PaymentMethod::Flutterwave => 0,
            PaymentMethod::WorldRemit => 1,
            PaymentMethod::Bank => 2,
            PaymentMethod::Pesapal => 3,
        };
    }

    /**
     * @return array<PaymentMethod>
     */
    public function enabledMethods(): array
    {
        return collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $method) => $this->isEnabled($method))
            ->sortBy(fn (PaymentMethod $method) => $this->displayOrder($method))
            ->values()
            ->all();
    }

    /**
     * Methods offered at checkout: enabled AND fully configured, in display
     * order. Unconfigured gateways (missing keys or payout details) are hidden
     * so the simulated checkout never appears in production.
     *
     * @return array<PaymentMethod>
     */
    public function usableMethods(): array
    {
        return collect(PaymentMethod::cases())
            ->filter(fn (PaymentMethod $method) => $this->isEnabled($method) && $this->isConfigured($method))
            ->sortBy(fn (PaymentMethod $method) => $this->displayOrder($method))
            ->values()
            ->all();
    }

    /**
     * Merged rows for every method, including stored settings, enabled state
     * and whether the method is fully configured.
     *
     * @return array<int, array{method: PaymentMethod, enabled: bool, configured: bool, settings: array<string, mixed>}>
     */
    public function all(): array
    {
        return collect(PaymentMethod::cases())->map(function (PaymentMethod $method) {
            $stored = $this->row($method);

            return [
                'method' => $method,
                'enabled' => $this->isEnabled($method),
                'configured' => $this->isConfigured($method),
                'settings' => array_merge($stored?->settings ?? [], config("payments.methods.{$method->value}", [])),
            ];
        })->values()->all();
    }

    public function update(PaymentMethod $method, bool $enabled, array $settings): PaymentMethodSetting
    {
        $row = PaymentMethodSetting::firstOrNew(['method' => $method->value]);

        $row->enabled = $enabled;
        $row->settings = $settings;
        $row->save();

        return $row;
    }

    private function row(PaymentMethod $method): ?PaymentMethodSetting
    {
        return PaymentMethodSetting::where('method', $method->value)->first();
    }
}
