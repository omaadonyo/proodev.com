<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Safety-net middleware for malformed UTF-8 in JSON responses.
 *
 * The primary fix is the FixUtf8JsonResponse service provider, which overrides
 * JsonResponse::setData() to always use JSON_INVALID_UTF8_SUBSTITUTE. This
 * middleware catches any remaining cases (e.g. third-party code creating
 * JsonResponse directly with custom encoding options).
 */
class SanitizeUtf8JsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (\InvalidArgumentException $e) {
            if (! str_contains($e->getMessage(), 'UTF-8')) {
                throw $e;
            }

            // If we get here, a JsonResponse was created without the macro
            // (unlikely but possible). Return a sanitized response.
            return response()->json(['data' => null, 'message' => 'Response contained invalid UTF-8 data.'], 200);
        }

        return $response;
    }
}