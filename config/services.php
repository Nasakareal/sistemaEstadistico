<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'waze' => [
        'feed_url' => env('WAZE_FEED_URL'),
        'morelia_user_ids' => array_values(array_filter(array_map('intval', explode(',', env('WAZE_MORELIA_USER_IDS', ''))))),
        'morelia_polygon' => [],
        'notify_radius_km' => (float) env('WAZE_NOTIFY_RADIUS_KM', 75),
        'notify_location_max_age_minutes' => (int) env('WAZE_NOTIFY_LOCATION_MAX_AGE_MINUTES', 720),
    ],

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'service_account' => env('FIREBASE_SERVICE_ACCOUNT'),
    ],

    'whatsapp' => [
        'graph_version' => env('WHATSAPP_GRAPH_VERSION', 'v19.0'),
        'token' => env('WHATSAPP_ACCESS_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'seguridadvial_token'),
        'default_to' => env('WHATSAPP_DEFAULT_TO'),

        'siniestros' => [
            'to' => env('WHATSAPP_SINIESTROS_TO'),
            'firma' => env('WHATSAPP_SINIESTROS_FIRMA', 'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS LIC. JULIO ERNESTO BAUTISTA JIMÉNEZ'),
            'resumen_to' => env('WHATSAPP_SINIESTROS_RESUMEN_TO'),
            'tarjeta_hechos_to' => env('WHATSAPP_SINIESTROS_TARJETA_HECHOS_TO'),
            'actividades_to' => env('WHATSAPP_SINIESTROS_ACTIVIDADES_TO'),
            'resumen_template' => env('WHATSAPP_SINIESTROS_RESUMEN_TEMPLATE'),
            'tarjeta_hechos_template' => env('WHATSAPP_SINIESTROS_TARJETA_HECHOS_TEMPLATE'),
        ],

        'todas_unidades' => [
            'to' => env('WHATSAPP_TODAS_UNIDADES_TO'),
            'template' => env('WHATSAPP_TODAS_UNIDADES_TEMPLATE', 'reporte_todas_unidades_diario'),
        ],

        'delegaciones' => [
            'alertas_to' => env('WHATSAPP_DELEGACIONES_ALERTAS_TO'),
            'incompletos_template' => env('WHATSAPP_DELEGACIONES_INCOMPLETOS_TEMPLATE', 'alerta_hecho_incompleto_delegaciones'),
            'incompletos_min_hours' => (int) env('WHATSAPP_DELEGACIONES_INCOMPLETOS_MIN_HOURS', 3),
            'incompletos_lookback_days' => (int) env('WHATSAPP_DELEGACIONES_INCOMPLETOS_LOOKBACK_DAYS', 3),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],
];
