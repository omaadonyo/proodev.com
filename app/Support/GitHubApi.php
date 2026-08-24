<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

/**
 * Thin GitHub REST client shared by every scout. Adds an optional personal
 * access token (GITHUB_TOKEN) to lift the 60 req/hour unauthenticated rate
 * limit, and reports accurate errors instead of treating every failure as
 * "not found".
 */
class GitHubApi
{
    public const USER_AGENT = 'ProoDev-Scout';

    /**
     * Fetch a JSON payload. Returns [] when the resource does not exist;
     * throws an accurate, user-facing message when the request fails for
     * any other reason (rate limits, server errors…).
     *
     * @return array<string, mixed>
     */
    public static function get(string $url): array
    {
        [$data, $status] = self::getWithStatus($url);

        if ($status === 404) {
            return [];
        }

        if ($status === 403 || $status === 429) {
            throw new InvalidArgumentException(
                'GitHub is temporarily limiting this scan (rate limit reached). Please try again in a few minutes'
                .(self::token() ? '.' : ', or add a GITHUB_TOKEN to your .env to raise the limit.'),
            );
        }

        if ($status < 200 || $status >= 300) {
            throw new InvalidArgumentException("GitHub could not process that request (HTTP {$status}). Please try again.");
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @return array{0: mixed, 1: int}
     */
    public static function getWithStatus(string $url): array
    {
        $headers = [
            'User-Agent' => self::USER_AGENT,
            'Accept' => 'application/vnd.github.mercy-preview+json',
        ];

        if ($token = self::token()) {
            $headers['Authorization'] = 'Bearer '.$token;
        }

        $response = Http::withHeaders($headers)->timeout(15)->get($url);

        return [$response->json(), $response->status()];
    }

    public static function token(): ?string
    {
        $token = trim((string) config('services.github.token', ''));

        return $token !== '' ? $token : null;
    }
}
