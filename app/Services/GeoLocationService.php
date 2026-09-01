<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * Maps free-text user locations to approximate lat/lng coordinates for the
 * landing-page globe. Known cities and countries resolve to real coordinates;
 * anything that cannot be resolved returns null so it is excluded from the globe.
 */
class GeoLocationService
{
    /**
     * @var array<string, array{0: float, 1: float}>
     */
    private const CITIES = [
        'berlin' => [52.52, 13.405],
        'germany' => [51.1657, 10.4515],
        'austin' => [30.2672, -97.7431],
        'london' => [51.5074, -0.1278],
        'uk' => [55.3781, -3.436],
        'milan' => [45.4642, 9.19],
        'italy' => [41.8719, 12.5674],
        'seoul' => [37.5665, 126.978],
        'south korea' => [35.9078, 127.7669],
        'prague' => [50.0755, 14.4378],
        'czech republic' => [49.8175, 15.473],
        'lisbon' => [38.7223, -9.1393],
        'portugal' => [39.3999, -8.2245],
        'singapore' => [1.3521, 103.8198],
        'dublin' => [53.3498, -6.2603],
        'ireland' => [53.4129, -8.2439],
        'copenhagen' => [55.6761, 12.5683],
        'denmark' => [56.2639, 9.5018],
        'vancouver' => [49.2827, -123.1207],
        'canada' => [56.1304, -106.3468],
        'new york' => [40.7128, -74.006],
        'san francisco' => [37.7749, -122.4194],
        'usa' => [37.0902, -95.7129],
        'us' => [37.0902, -95.7129],
        'paris' => [48.8566, 2.3522],
        'france' => [46.2276, 2.2137],
        'tokyo' => [35.6762, 139.6503],
        'japan' => [36.2048, 138.2529],
        'sydney' => [-33.8688, 151.2093],
        'australia' => [-25.2744, 133.7751],
        'toronto' => [43.6532, -79.3832],
        'amsterdam' => [52.3676, 4.9041],
        'netherlands' => [52.1326, 5.2913],
        'zurich' => [47.3769, 8.5417],
        'switzerland' => [46.8182, 8.2275],
        'mumbai' => [19.076, 72.8777],
        'india' => [20.5937, 78.9629],
        'lagos' => [6.5244, 3.3792],
        'nigeria' => [9.082, 8.6753],
        'nairobi' => [-1.2921, 36.8219],
        'kenya' => [-0.0236, 37.9062],
        'sao paulo' => [-23.5505, -46.6333],
        'brazil' => [-14.235, -51.9253],
        'barcelona' => [41.3874, 2.1686],
        'madrid' => [40.4168, -3.7038],
        'spain' => [40.4637, -3.7492],
        'warsaw' => [52.2297, 21.0122],
        'poland' => [51.9194, 19.1451],
        'mexico city' => [19.4326, -99.1332],
        'mexico' => [23.6345, -102.5528],
        'cape town' => [-33.9249, 18.4241],
        'south africa' => [-30.5595, 22.9375],
        'bangkok' => [13.7563, 100.5018],
        'thailand' => [15.87, 100.9925],
    ];

    /**
     * Resolve a location string to approximate [lat, lng].
     *
     * Returns null when the location cannot be mapped to a known place, so
     * developers with missing or unrecognized locations are excluded from the
     * globe instead of being plotted at arbitrary coordinates.
     *
     * @return array{lat: float, lng: float}|null
     */
    public static function resolve(?string $location): ?array
    {
        $location = Str::lower((string) $location);

        foreach (self::CITIES as $needle => $coords) {
            if (Str::contains($location, $needle)) {
                return ['lat' => $coords[0], 'lng' => $coords[1]];
            }
        }

        return null;
    }
}
