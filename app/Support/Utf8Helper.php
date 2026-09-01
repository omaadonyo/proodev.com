<?php

namespace App\Support;

use Illuminate\Support\Collection;

class Utf8Helper
{
    /**
     * Recursively sanitize a value (string, array, or object) ensuring
     * all strings are valid UTF-8. Non-UTF-8 bytes are replaced with
     * the Unicode replacement character (U+FFFD).
     */
    public static function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return mb_check_encoding($value, 'UTF-8')
                ? $value
                : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        }

        if (is_array($value)) {
            return array_map([self::class, 'sanitize'], $value);
        }

        if ($value instanceof Collection) {
            return $value->map([self::class, 'sanitize']);
        }

        if (is_object($value) && method_exists($value, 'toArray')) {
            // Eloquent models — leave them untouched; Livewire handles serialization.
        }

        return $value;
    }

    /**
     * Strip invalid UTF-8 bytes from a string, replacing them with the
     * Unicode replacement character.
     */
    public static function cleanString(string $value): string
    {
        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}
