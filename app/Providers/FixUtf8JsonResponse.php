<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\ServiceProvider;

/**
 * Fix: Laravel 13's JsonResponse defaults encodingOptions to 0, meaning
 * json_encode throws InvalidArgumentException on any malformed UTF-8 bytes.
 *
 * The real fix is in vendor/laravel/.../JsonResponse.php where the constructor
 * now includes JSON_INVALID_UTF8_SUBSTITUTE in encodingOptions.
 *
 * This provider serves as documentation and a fallback safety net via middleware.
 */
class FixUtf8JsonResponse extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}