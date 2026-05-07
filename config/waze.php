<?php

return [
    'feed_token' => env('WAZE_FEED_TOKEN', 'seguridad_vial_michoacan_waze_feed_2026_x9Pq7Lm2Aa8Rt1Kz4Vn6Hd3Bw'),
    'hours_back' => env('WAZE_FEED_HOURS_BACK', 24),
    'default_incident_minutes' => env('WAZE_DEFAULT_INCIDENT_MINUTES', 120),
    'default_closure_minutes' => env('WAZE_DEFAULT_CLOSURE_MINUTES', 30),
    'publish_accidents_as_closures' => env('WAZE_PUBLISH_ACCIDENTS_AS_CLOSURES', false),
];
