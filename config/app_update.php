<?php

return [
    'android_min_version' => env('ANDROID_MIN_VERSION', '1.0.0'),
    'android_latest_version' => env('ANDROID_LATEST_VERSION', '1.0.0'),
    'android_force_update' => env('ANDROID_FORCE_UPDATE', false),
    'android_download_url' => env('ANDROID_DOWNLOAD_URL', env('ANDROID_STORE_URL')),
];
