<?php

return [
    'enabled' => filter_var(env('HONEYPOT_API_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'prefix' => trim(env('HONEYPOT_API_PREFIX', 'v1/legacy'), '/'),
    'enumeration_threshold' => max(3, (int) env('HONEYPOT_ENUMERATION_THRESHOLD', 5)),
    'enumeration_window_minutes' => max(1, (int) env('HONEYPOT_ENUMERATION_WINDOW_MINUTES', 10)),
    'mass_query_limit' => max(50, (int) env('HONEYPOT_MASS_QUERY_LIMIT', 250)),
    'max_inspection_bytes' => max(4096, (int) env('HONEYPOT_MAX_INSPECTION_BYTES', 32768)),
    'geolocation' => [
        'enabled' => filter_var(env('HONEYPOT_GEOLOCATION_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
        'provider' => env('HONEYPOT_GEOLOCATION_PROVIDER', 'ipinfo'),
        'url' => env('HONEYPOT_GEOLOCATION_URL', 'https://ipinfo.io/{ip}/json'),
        'token' => env('HONEYPOT_GEOLOCATION_TOKEN'),
        'timeout_seconds' => max(2, min(15, (int) env('HONEYPOT_GEOLOCATION_TIMEOUT_SECONDS', 4))),
        'cache_days' => max(1, (int) env('HONEYPOT_GEOLOCATION_CACHE_DAYS', 7)),
        'batch_size' => max(1, min(500, (int) env('HONEYPOT_GEOLOCATION_BATCH_SIZE', 50))),
    ],
];
