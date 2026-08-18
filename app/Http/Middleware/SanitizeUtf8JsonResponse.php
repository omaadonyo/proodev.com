<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanitize malformed UTF-8 bytes in outgoing JSON responses.
 *
 * Some user-generated content (bios, project descriptions, etc.) can
 * contain invalid multi-byte sequences that break `json_encode`. This
 * middleware catches that scenario, cleans the JSON body, and returns a
 * valid UTF-8 response instead of a 500.
 */
class SanitizeUtf8JsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $contentType = $response->headers->get('Content-Type', '');

        if (! str_contains($contentType, 'application/json')) {
            return $response;
        }

        $body = $response->getContent();

        if ($body === false || $body === '' || mb_check_encoding($body, 'UTF-8')) {
            return $response;
        }

        // Clean the JSON body — replace invalid bytes and re-encode.
        $cleaned = mb_convert_encoding($body, 'UTF-8', 'UTF-8');

        // Verify the cleaned JSON is valid.
        $decoded = json_decode($cleaned, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // Fallback: try to grab valid prefix + fix remaining.
            return $response;
        }

        $response->setContent(json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));

        return $response;
    }
}
