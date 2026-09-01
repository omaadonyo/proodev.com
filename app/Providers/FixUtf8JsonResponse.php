<?php

namespace App\Providers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\ServiceProvider;

/**
 * Fix: Laravel 13's JsonResponse defaults encodingOptions to 0, meaning
 * json_encode throws InvalidArgumentException on any malformed UTF-8 bytes.
 *
 * Livewire update responses include re-rendered HTML + dispatch params
 * (raw arrays) that may contain invalid UTF-8 from user text or external
 * sources (GitHub profile data, chat messages, etc.).
 *
 * This macro overrides setData() to always include JSON_INVALID_UTF8_SUBSTITUTE,
 * which replaces bad bytes with U+FFFD instead of crashing.
 */
class FixUtf8JsonResponse extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        JsonResponse::macro('setData', function ($content) {
            $encodingOptions = $this->getEncodingOptions() | JSON_INVALID_UTF8_SUBSTITUTE;

            $this->content = $content instanceof \Symfony\Component\HttpFoundation\StreamedResponse
                ? $content
                : json_encode($content, $encodingOptions);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException(json_last_error_msg());
            }

            $this->headers->set('Content-Type', 'application/json');

            return $this;
        });
    }
}