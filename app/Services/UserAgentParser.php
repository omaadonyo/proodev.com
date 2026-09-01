<?php

namespace App\Services;

/**
 * Lightweight, dependency-free user agent parser used for admin analytics.
 * Resolves a raw user agent string into a device type, operating system and
 * browser label without external packages or network calls.
 */
class UserAgentParser
{
    /**
     * Parse a user agent string.
     *
     * @return array{type: 'desktop'|'mobile'|'tablet'|'bot'|'unknown', os: string, browser: string}
     */
    public static function parse(?string $userAgent): array
    {
        $ua = strtolower((string) $userAgent);

        $type = self::deviceType($ua);
        $os = self::operatingSystem($ua);
        $browser = self::browser($ua);

        return compact('type', 'os', 'browser');
    }

    private static function deviceType(string $ua): string
    {
        if (preg_match('/(bot|crawler|spider|slurp|bingpreview|googlebot|curl|wget|headless|python-requests)/', $ua)) {
            return 'bot';
        }

        if (str_contains($ua, 'ipad') || preg_match('/(tablet|silk|playbook|kindle)/', $ua)) {
            return 'tablet';
        }

        if (preg_match('/(mobile|android.+|iphone|ipod|blackberry|opera mini|windows phone)/', $ua)) {
            return 'mobile';
        }

        return 'desktop';
    }

    private static function operatingSystem(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone'), str_contains($ua, 'ipod') => 'iOS',
            str_contains($ua, 'ipad') => 'iPadOS',
            str_contains($ua, 'mac os') || str_contains($ua, 'macintosh') => 'macOS',
            str_contains($ua, 'linux') => 'Linux',
            str_contains($ua, 'cros') => 'ChromeOS',
            default => 'Unknown',
        };
    }

    private static function browser(string $ua): string
    {
        return match (true) {
            str_contains($ua, 'edg/') || str_contains($ua, 'edge/') => 'Edge',
            str_contains($ua, 'opr/') || str_contains($ua, 'opera') => 'Opera',
            str_contains($ua, 'firefox/') => 'Firefox',
            str_contains($ua, 'chrome/') => 'Chrome',
            str_contains($ua, 'safari/') => 'Safari',
            str_contains($ua, 'bingbot') => 'Bingbot',
            str_contains($ua, 'googlebot') => 'Googlebot',
            str_contains($ua, 'curl/') => 'curl',
            default => 'Unknown',
        };
    }
}
