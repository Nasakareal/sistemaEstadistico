<?php

return [
    'feed_token' => env('WAZE_FEED_TOKEN', 'seguridad_vial_michoacan_waze_feed_2026_x9Pq7Lm2Aa8Rt1Kz4Vn6Hd3Bw'),
    'hours_back' => env('WAZE_FEED_HOURS_BACK', 24),
    'default_incident_minutes' => env('WAZE_DEFAULT_INCIDENT_MINUTES', 120),
    'default_closure_minutes' => env('WAZE_DEFAULT_CLOSURE_MINUTES', 30),
    'publish_accidents_as_closures' => env('WAZE_PUBLISH_ACCIDENTS_AS_CLOSURES', false),
    'reverse_geocoding_enabled' => filter_var(env('WAZE_REVERSE_GEOCODING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'reverse_geocoding_token' => env('WAZE_REVERSE_GEOCODING_TOKEN', ''),
    'reverse_geocoding_endpoint' => env('WAZE_REVERSE_GEOCODING_ENDPOINT', 'https://www.waze.com/row-partnerhub-api/waze-map/streetsInfo'),
    'reverse_geocoding_timeout' => env('WAZE_REVERSE_GEOCODING_TIMEOUT', 2),
    'reverse_geocoding_cache_seconds' => env('WAZE_REVERSE_GEOCODING_CACHE_SECONDS', 604800),
    'reverse_geocoding_max_distance_meters' => env('WAZE_REVERSE_GEOCODING_MAX_DISTANCE_METERS', 50),
    'require_reverse_geocoding_match' => filter_var(env('WAZE_REQUIRE_REVERSE_GEOCODING_MATCH', false), FILTER_VALIDATE_BOOLEAN),
];
