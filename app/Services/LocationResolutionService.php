<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LocationResolutionService
{
    /**
     * Non-landmark administrative or routing types to exclude when selecting POIs.
     */
    private const EXCLUDED_TYPES = [
        'political',
        'country',
        'administrative_area_level_1',
        'administrative_area_level_2',
        'administrative_area_level_3',
        'administrative_area_level_4',
        'administrative_area_level_5',
        'locality',
        'sublocality',
        'sublocality_level_1',
        'sublocality_level_2',
        'neighborhood',
        'route',
        'street_address',
        'intersection',
        'plus_code',
        'postal_code',
    ];

    /**
     * Resolve latitude and longitude to a nearby Point of Interest (POI) landmark,
     * falling back gracefully to reverse geocoding if no POI is found.
     *
     * IMPORTANT: The returned delivery_lat and delivery_lng remain the authoritative
     * customer pin coordinates. The POI is only descriptive.
     *
     * @param float $lat Customer pin latitude
     * @param float $lng Customer pin longitude
     * @param float $radiusMeters Search radius around pin (default 500m)
     * @return array{
     *     delivery_lat: float,
     *     delivery_lng: float,
     *     delivery_place_name: ?string,
     *     delivery_address: string,
     *     delivery_place_id: ?string,
     *     location_source: string,
     *     distance_meters: float
     * }
     */
    public function resolve(float $lat, float $lng, float $radiusMeters = 500.0): array
    {
        $roundLat = round($lat, 5);
        $roundLng = round($lng, 5);
        $cacheKey = "loc_res_{$roundLat}_{$roundLng}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($lat, $lng, $radiusMeters) {
            // 1. Attempt Nearby POI resolution via Google Places API (New)
            $googlePoi = $this->searchNearbyGooglePlaces($lat, $lng, $radiusMeters);
            if ($googlePoi !== null) {
                return [
                    'delivery_lat'        => $lat,
                    'delivery_lng'        => $lng,
                    'delivery_place_name' => $googlePoi['name'],
                    'delivery_address'    => $googlePoi['formatted_address'],
                    'delivery_place_id'   => $googlePoi['place_id'],
                    'location_source'     => 'google_places',
                    'distance_meters'     => $googlePoi['distance_meters'],
                ];
            }

            // 2. Fallback: Reverse Geocoding via Nominatim / Photon
            $fallback = $this->reverseGeocodeFallback($lat, $lng);

            return [
                'delivery_lat'        => $lat,
                'delivery_lng'        => $lng,
                'delivery_place_name' => $fallback['place_name'],
                'delivery_address'    => $fallback['address'],
                'delivery_place_id'   => null,
                'location_source'     => $fallback['source'],
                'distance_meters'     => 0.0,
            ];
        });
    }

    /**
     * Query Google Places API (New) Nearby Search.
     * Endpoint: POST https://places.googleapis.com/v1/places:searchNearby
     *
     * Returns the closest relevant establishment/landmark within radius, or null.
     */
    private function searchNearbyGooglePlaces(float $lat, float $lng, float $radiusMeters): ?array
    {
        $apiKey = config('services.google.places_api_key') ?: config('services.google.maps_api_key');
        if (empty($apiKey)) {
            return null;
        }

        try {
            $url = 'https://places.googleapis.com/v1/places:searchNearby';
            $fieldMask = 'places.id,places.displayName,places.formattedAddress,places.location,places.types,places.primaryType';

            $payload = [
                'maxResultCount'      => 10,
                'locationRestriction' => [
                    'circle' => [
                        'center' => [
                            'latitude'  => $lat,
                            'longitude' => $lng,
                        ],
                        'radius' => (float) $radiusMeters,
                    ],
                ],
            ];

            $response = Http::timeout(5)
                ->withoutVerifying()
                ->withHeaders([
                    'Content-Type'     => 'application/json',
                    'X-Goog-Api-Key'   => $apiKey,
                    'X-Goog-FieldMask' => $fieldMask,
                ])
                ->post($url, $payload);

            if (! $response->successful()) {
                Log::warning('Google Places Nearby Search API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'lat'    => $lat,
                    'lng'    => $lng,
                ]);
                return null;
            }

            $data = $response->json();
            $places = $data['places'] ?? [];

            if (empty($places) || ! is_array($places)) {
                return null;
            }

            // Filter out non-landmark/administrative entities and compute distance
            $candidates = [];
            foreach ($places as $place) {
                $name = $place['displayName']['text'] ?? null;
                if (! $name) {
                    continue;
                }

                $types = $place['types'] ?? [];
                if ($this->isExclusivelyAdministrative($types)) {
                    continue;
                }

                $placeLat = $place['location']['latitude'] ?? null;
                $placeLng = $place['location']['longitude'] ?? null;

                $distMeters = ($placeLat !== null && $placeLng !== null)
                    ? $this->calculateHaversineDistance($lat, $lng, (float) $placeLat, (float) $placeLng) * 1000.0
                    : 9999.0;

                // Only consider POIs within search radius
                if ($distMeters <= $radiusMeters) {
                    $candidates[] = [
                        'name'              => trim($name),
                        'formatted_address' => trim($place['formattedAddress'] ?? $name),
                        'place_id'          => $place['id'] ?? null,
                        'types'             => $types,
                        'distance_meters'   => round($distMeters, 1),
                        'place_lat'         => $placeLat,
                        'place_lng'         => $placeLng,
                    ];
                }
            }

            if (empty($candidates)) {
                return null;
            }

            // Sort candidates by distance (closest relevant POI first)
            usort($candidates, fn ($a, $b) => $a['distance_meters'] <=> $b['distance_meters']);

            return $candidates[0];
        } catch (\Throwable $e) {
            Log::warning('Google Places Nearby Search Exception: ' . $e->getMessage(), [
                'lat' => $lat,
                'lng' => $lng,
            ]);
            return null;
        }
    }

    /**
     * Check if a set of Google Places types contains only administrative/road categories.
     */
    private function isExclusivelyAdministrative(array $types): bool
    {
        if (empty($types)) {
            return false;
        }

        foreach ($types as $type) {
            if (! in_array($type, self::EXCLUDED_TYPES, true)) {
                return false; // Found a valid non-administrative type (e.g. school, hospital, point_of_interest)
            }
        }

        return true;
    }

    /**
     * Fallback reverse geocode using Nominatim / Photon.
     * Returns human-readable street/locality address and any named amenity found.
     */
    private function reverseGeocodeFallback(float $lat, float $lng): array
    {
        $roundLat = round($lat, 5);
        $roundLng = round($lng, 5);

        // 1. Try Nominatim reverse geocode
        try {
            $nomUrl = "https://nominatim.openstreetmap.org/reverse?format=json&lat={$roundLat}&lon={$roundLng}&zoom=18&addressdetails=1";
            $nomRes = Http::timeout(4)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Foodio-RestaurantBot/1.0'])
                ->get($nomUrl);

            if ($nomRes->successful()) {
                $nomData = $nomRes->json();
                $addr = $nomData['address'] ?? [];

                // Check for named amenity / landmark in OSM
                $poi = $addr['amenity'] ?? $addr['shop'] ?? $addr['building'] ?? $addr['healthcare'] ?? $addr['tourism'] ?? $nomData['name'] ?? null;
                $road = $addr['road'] ?? $addr['highway'] ?? $addr['street'] ?? '';
                $locality = $addr['suburb'] ?? $addr['neighbourhood'] ?? $addr['village'] ?? $addr['hamlet'] ?? $addr['town'] ?? $addr['city'] ?? '';

                $parts = array_filter([$poi, $road, $locality]);
                if (! empty($parts)) {
                    $formatted = implode(', ', array_unique($parts));
                    return [
                        'place_name' => $poi ? trim($poi) : null,
                        'address'    => $formatted,
                        'source'     => 'reverse_geocode',
                    ];
                }

                if (! empty($nomData['display_name'])) {
                    $rawParts = array_map('trim', explode(',', $nomData['display_name']));
                    $meaningful = array_slice($rawParts, 0, min(count($rawParts), 4));
                    return [
                        'place_name' => null,
                        'address'    => implode(', ', $meaningful),
                        'source'     => 'reverse_geocode',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info('Nominatim fallback exception: ' . $e->getMessage());
        }

        // 2. Try Photon reverse geocode
        try {
            $phoUrl = "https://photon.komoot.io/reverse?lat={$roundLat}&lon={$roundLng}&lang=en";
            $phoRes = Http::timeout(4)
                ->withoutVerifying()
                ->get($phoUrl);

            if ($phoRes->successful()) {
                $phoData = $phoRes->json();
                $props = $phoData['features'][0]['properties'] ?? [];
                $name = $props['name'] ?? '';
                $street = $props['street'] ?? '';
                $city = $props['city'] ?? $props['district'] ?? '';

                $parts = array_filter(array_unique([$name, $street, $city]));
                if (! empty($parts)) {
                    return [
                        'place_name' => (! empty($name) && $name !== $street) ? $name : null,
                        'address'    => implode(', ', $parts),
                        'source'     => 'reverse_geocode',
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::info('Photon fallback exception: ' . $e->getMessage());
        }

        // Default coordinate representation if all geocoders are unreachable
        return [
            'place_name' => null,
            'address'    => "Selected Pin Location ({$roundLat}, {$roundLng})",
            'source'     => 'reverse_geocode',
        ];
    }

    /**
     * Haversine distance in kilometers between two GPS coordinates.
     */
    public function calculateHaversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }

    public function calculateDistanceKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return $this->calculateHaversineDistance($lat1, $lon1, $lat2, $lon2);
    }


    /**
     * Forward geocode a text address into [lat, lng].
     * Tries Nominatim with strict city / restaurant bounding box, then Photon fallback.
     * Rejects any points that fall outside the local area (e.g. > 35 km away).
     */
    public function geocodeAddress(string $address, string $city = '', ?float $centerLat = null, ?float $centerLng = null, float $searchRadiusKm = 30.0): ?array
    {
        $clean = trim($address);
        if ($clean === '' || strlen($clean) < 3) {
            return null;
        }

        $clean = preg_replace('/[#*`~_]/', ' ', $clean);
        $clean = trim(preg_replace('/\s+/', ' ', $clean));

        $cacheKey = 'fwd_geocode_' . md5(strtolower($clean . '_' . $city . '_' . (string)$centerLat . '_' . (string)$centerLng));
        return Cache::remember($cacheKey, now()->addDays(7), function () use ($clean, $city, $centerLat, $centerLng, $searchRadiusKm) {
            $queriesToTry = [];

            if ($city && stripos($clean, $city) === false) {
                $queriesToTry[] = "{$clean}, {$city}, Pakistan";
            }
            $queriesToTry[] = "{$clean}, Pakistan";

            // Compute bounding box if centerLat and centerLng are provided
            $viewboxParam = '';
            if ($centerLat !== null && $centerLng !== null) {
                $latDelta = $searchRadiusKm / 111.0;
                $cosLat = cos(deg2rad($centerLat));
                $lngDelta = $searchRadiusKm / (111.0 * ($cosLat > 0.01 ? $cosLat : 1.0));
                $minLat = $centerLat - $latDelta;
                $maxLat = $centerLat + $latDelta;
                $minLng = $centerLng - $lngDelta;
                $maxLng = $centerLng + $lngDelta;
                $viewboxParam = "&viewbox={$minLng},{$maxLat},{$maxLng},{$minLat}&bounded=1";
            }

            // 1. Try Nominatim (with bounded viewbox)
            foreach ($queriesToTry as $q) {
                try {
                    $url = "https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&limit=3&q=" . urlencode($q) . $viewboxParam;
                    $res = Http::timeout(4)
                        ->withoutVerifying()
                        ->withHeaders(['User-Agent' => 'Foodio-RestaurantBot/1.0'])
                        ->get($url);

                    if ($res->successful()) {
                        $data = $res->json();
                        if (is_array($data)) {
                            foreach ($data as $item) {
                                if (empty($item['lat']) || empty($item['lon'])) {
                                    continue;
                                }
                                $lat = (float) $item['lat'];
                                $lng = (float) $item['lon'];

                                // Validate against center if provided
                                if ($centerLat !== null && $centerLng !== null) {
                                    $dist = $this->calculateDistanceKm($centerLat, $centerLng, $lat, $lng);
                                    if ($dist > ($searchRadiusKm + 10.0)) {
                                        // Out of local region (e.g. Karachi 680km away) -> Discard!
                                        continue;
                                    }
                                }

                                // Ignore broad administrative boundaries (district, province, country)
                                $type = $item['type'] ?? '';
                                $class = $item['class'] ?? '';
                                if ($class === 'boundary' || in_array($type, ['administrative', 'state', 'country', 'district', 'division'], true)) {
                                    continue;
                                }

                                $name = $item['display_name'] ?? '';
                                if (!$this->hasDistinctiveTokenMatch($clean, $name)) {
                                    continue;
                                }

                                return [
                                    'lat'          => $lat,
                                    'lng'          => $lng,
                                    'display_name' => $name,
                                ];
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("Nominatim geocoding failed for '{$q}': " . $e->getMessage());
                }
            }

            // 2. Try Photon fallback (with proximity bias & strict distance validation)
            $photonParams = '';
            if ($centerLat !== null && $centerLng !== null) {
                $photonParams = "&lat={$centerLat}&lon={$centerLng}";
            }

            foreach ($queriesToTry as $q) {
                try {
                    $url = "https://photon.komoot.io/api/?limit=3&q=" . urlencode($q) . $photonParams;
                    $res = Http::timeout(4)
                        ->withoutVerifying()
                        ->get($url);

                    if ($res->successful()) {
                        $data = $res->json();
                        $features = $data['features'] ?? [];
                        foreach ($features as $f) {
                            $coords = $f['geometry']['coordinates'] ?? null;
                            if (empty($coords[0]) || empty($coords[1])) {
                                continue;
                            }
                            $lat = (float) $coords[1];
                            $lng = (float) $coords[0];
                            $matchedName = $f['properties']['name'] ?? '';

                            // Validate distance from restaurant center
                            if ($centerLat !== null && $centerLng !== null) {
                                $dist = $this->calculateDistanceKm($centerLat, $centerLng, $lat, $lng);
                                if ($dist > ($searchRadiusKm + 10.0)) {
                                    // Returned a point in another city/province -> Discard!
                                    continue;
                                }
                            }

                            if (!$this->hasDistinctiveTokenMatch($clean, $matchedName)) {
                                continue;
                            }

                            return [
                                'lat'          => $lat,
                                'lng'          => $lng,
                                'display_name' => $matchedName,
                            ];
                        }
                    }
                } catch (\Throwable $e) {
                    Log::info("Photon geocoding failed for '{$q}': " . $e->getMessage());
                }
            }

            return null;
        });
    }

    protected function hasDistinctiveTokenMatch(string $query, string $matchedText): bool
    {
        $commonWords = ['near', 'chowk', 'road', 'gali', 'house', 'flat', 'street', 'main', 'basti', 'chak', 'city', 'town', 'pakistan', 'punjab', 'district', 'tehsil'];
        $queryTokens = array_filter(preg_split('/[\s,\.\-_]+/', strtolower($query)), fn($w) => strlen($w) >= 3 && !in_array($w, $commonWords, true));
        if (empty($queryTokens)) {
            return true;
        }
        $lowerMatched = strtolower($matchedText);
        foreach ($queryTokens as $tok) {
            if (str_contains($lowerMatched, $tok)) {
                return true;
            }
        }
        return false;
    }
}
