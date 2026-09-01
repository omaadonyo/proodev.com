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
        'accra' => [5.6037, -0.187],
        'ghana' => [7.9465, -1.0232],
        'cairo' => [30.0444, 31.2357],
        'egypt' => [26.8206, 30.8025],
        'dubai' => [25.2048, 55.2708],
        'uae' => [23.4241, 53.8478],
        'united arab emirates' => [23.4241, 53.8478],
        'taipei' => [25.033, 121.5654],
        'taiwan' => [23.5937, 121.025],
        'colombo' => [6.9271, 79.8612],
        'sri lanka' => [7.8731, 80.7718],
        'ho chi minh' => [10.8231, 106.6297],
        'vietnam' => [14.0583, 108.2772],
        'karachi' => [24.8607, 67.0011],
        'pakistan' => [30.3753, 69.3451],
        'jakarta' => [-6.2088, 106.8456],
        'indonesia' => [-0.7893, 113.9213],
        'manila' => [14.5995, 120.9842],
        'philippines' => [12.8797, 121.774],
        'kuala lumpur' => [3.139, 101.6869],
        'malaysia' => [4.2105, 101.9758],
        'dhaka' => [23.8103, 90.4125],
        'bangladesh' => [23.6849, 90.3563],
        'istanbul' => [41.0082, 28.9784],
        'turkey' => [38.9637, 35.2433],
        'tel aviv' => [32.0853, 34.7818],
        'israel' => [31.0461, 34.8516],
        'riyadh' => [24.7136, 46.6753],
        'saudi arabia' => [23.8859, 45.0792],
        'doha' => [25.2854, 51.531],
        'qatar' => [25.3548, 51.1839],
        'kampala' => [0.3476, 32.5825],
        'uganda' => [1.3733, 32.2903],
        'dar es salaam' => [-6.7924, 39.2083],
        'tanzania' => [-6.369, 34.8888],
        'addis ababa' => [9.03, 38.74],
        'ethiopia' => [9.145, 40.4897],
        'casablanca' => [33.5731, -7.5898],
        'morocco' => [31.7917, -7.0926],
        'algiers' => [36.7538, 3.0588],
        'algeria' => [28.0339, 1.6596],
        'buenos aires' => [-34.6037, -58.3816],
        'argentina' => [-38.4161, -63.6167],
        'santiago' => [-33.4489, -70.6693],
        'chile' => [-35.6751, -71.543],
        'bogota' => [4.711, -74.0721],
        'colombia' => [4.5709, -74.2973],
        'lima' => [-12.0464, -77.0428],
        'peru' => [-9.19, -75.0152],
        'johannesburg' => [-26.2041, 28.0473],
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
