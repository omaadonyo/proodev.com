<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolves an IP address to a country name for admin analytics.
 *
 * Uses a free public endpoint (ip-api.com) and caches results for a day so the
 * database-backed cache store absorbs repeated lookups. Private and reserved
 * ranges resolve to "Local" without hitting the network, keeping local
 * development and tests deterministic.
 */
class IpCountryResolver
{
    public const UNKNOWN = 'Unknown';

    /**
     * Resolve an IP address to a country name.
     */
    public static function country(?string $ip): string
    {
        if (empty($ip) || static::isLocal($ip)) {
            return 'Local';
        }

        return Cache::remember(
            "ip-country:{$ip}",
            now()->addDay(),
            fn (): string => static::lookup($ip),
        );
    }

    /**
     * Best-effort network lookup, falling back to "Unknown" on any failure.
     */
    private static function lookup(string $ip): string
    {
        try {
            $response = Http::timeout(2)
                ->retry(1, 200)
                ->get('http://ip-api.com/json/'.$ip, ['fields' => 'status,country']);

            if ($response->successful() && $response->json('status') === 'success') {
                return (string) $response->json('country', self::UNKNOWN);
            }
        } catch (\Throwable) {
            // Offline or blocked network: fall through.
        }

        return self::UNKNOWN;
    }

    /**
     * Determine whether an IP is a private / reserved / local address.
     */
    private static function isLocal(?string $ip): bool
    {
        if ($ip === null || $ip === '' || $ip === '::1') {
            return true;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        $parts = array_map('intval', explode('.', $ip));

        return $parts[0] === 10
            || $parts[0] === 127
            || ($parts[0] === 172 && $parts[1] >= 16 && $parts[1] <= 31)
            || ($parts[0] === 192 && $parts[1] === 168)
            || ($parts[0] === 0)
            || ($parts[0] === 169 && $parts[1] === 254);
    }
}
