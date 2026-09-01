<?php

namespace App\Support;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Display-only currency formatting for the admin dashboards. Admins can flip
 * between USD and UGX while reviewing sales; payments are always recorded in
 * the base currency, so this helper only ever converts for presentation.
 */
class BillingCurrency
{
    public const CODES = ['usd', 'ugx'];

    public static function rate(string $code): float
    {
        return match (strtolower($code)) {
            'ugx' => (float) config('billing.exchange_rates.ugx_per_usd', 3800),
            default => 1.0,
        };
    }

    public static function convert(float $usdAmount, string $code): float
    {
        $code = strtolower($code);

        return round($usdAmount * self::rate($code), $code === 'ugx' ? 0 : 2);
    }

    public static function format(float $usdAmount, string $code): string
    {
        $code = strtolower($code);

        return number_format(self::convert($usdAmount, $code), $code === 'ugx' ? 0 : 2).' '.strtoupper($code);
    }

    /**
     * The display currency the given admin prefers, persisted on the account
     * itself so the choice follows them across browsers and sessions. Falls
     * back to USD when the account has no preference set.
     */
    public static function codeFor(?Authenticatable $user = null): string
    {
        $code = strtolower((string) ($user?->billing_currency ?? 'usd'));

        return in_array($code, self::CODES, true) ? $code : 'usd';
    }

    /**
     * Persist an admin's display-currency preference on their account.
     */
    public static function setCodeFor(?Authenticatable $user, string $code): void
    {
        if (! $user) {
            return;
        }

        $code = strtolower($code);

        $user->forceFill(['billing_currency' => in_array($code, self::CODES, true) ? $code : null])->save();
    }
}
