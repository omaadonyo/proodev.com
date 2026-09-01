<?php

namespace App\Support;

use Laravel\Pennant\Feature;

/**
 * Platform-wide feature checks.
 *
 * All flags are resolved with an explicit `null` scope so that admin toggles
 * stored on the global (``__laravel_null``) Pennant scope are authoritative
 * for every visitor, regardless of the authenticated user's scope.
 */
final class FeatureFlags
{
    public static function active(string $feature): bool
    {
        return Feature::for(null)->active($feature);
    }

    public static function publicPresenceEnabled(): bool
    {
        return self::active('public-presence');
    }
}
