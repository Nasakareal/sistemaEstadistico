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

    'inegi_choques' => [
        'mail_to' => env('INEGI_CHOQUES_MAIL_TO', ''),
        'mail_cc' => env('INEGI_CHOQUES_MAIL_CC', ''),
        'mail_bcc' => env('INEGI_CHOQUES_MAIL_BCC', ''),
        'schedule_time' => env('INEGI_CHOQUES_SCHEDULE_TIME', '04:30'),
        'template_path' => env('INEGI_CHOQUES_TEMPLATE_PATH', ''),
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

        'equinos_bridge' => [
            'enabled' => filter_var(env('WHATSAPP_EQUINOS_BRIDGE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'url' => env('WHATSAPP_EQUINOS_BRIDGE_URL', 'https://equinosycaninos.com/api/whatsapp/webhook'),
            'phones' => array_values(array_filter(array_map('trim', explode(',', env('WHATSAPP_EQUINOS_BRIDGE_PHONES', ''))))),
            'timeout' => (int) env('WHATSAPP_EQUINOS_BRIDGE_TIMEOUT', 60),
        ],

        'siniestros' => [
            'to' => env('WHATSAPP_SINIESTROS_TO'),
            'firma' => env('WHATSAPP_SINIESTROS_FIRMA', 'SUBDIRECTOR DE LA UNIDAD DE ATENCIÓN A SINIESTROS LIC. JULIO ERNESTO BAUTISTA JIMÉNEZ'),
            'resumen_to' => env('WHATSAPP_SINIESTROS_RESUMEN_TO'),
            'tarjeta_hechos_to' => env('WHATSAPP_SINIESTROS_TARJETA_HECHOS_TO'),
            'actividades_to' => env('WHATSAPP_SINIESTROS_ACTIVIDADES_TO'),
            'vialidades_urbanas_alertas_to' => env('WHATSAPP_SINIESTROS_VIALIDADES_URBANAS_ALERTAS_TO'),
            'resumen_template' => env('WHATSAPP_SINIESTROS_RESUMEN_TEMPLATE'),
            'tarjeta_hechos_template' => env('WHATSAPP_SINIESTROS_TARJETA_HECHOS_TEMPLATE'),
            'actividades_template' => env('WHATSAPP_SINIESTROS_ACTIVIDADES_TEMPLATE'),
            'actividades_template_language' => env('WHATSAPP_SINIESTROS_ACTIVIDADES_TEMPLATE_LANGUAGE', 'es_MX'),
            'vialidades_urbanas_alertas_template' => env('WHATSAPP_SINIESTROS_VIALIDADES_URBANAS_ALERTAS_TEMPLATE', 'alerta_vialidades_urbanas_siniestros'),
            'vialidades_urbanas_alertas_template_language' => env('WHATSAPP_SINIESTROS_VIALIDADES_URBANAS_ALERTAS_TEMPLATE_LANGUAGE', 'es_MX'),
        ],

        'vialidades_urbanas' => [
            'to' => env('WHATSAPP_VIALIDADES_URBANAS_TO'),
            'template' => env('WHATSAPP_VIALIDADES_URBANAS_TEMPLATE', 'reporte_vialidades_urbanas_bloque'),
            'template_language' => env('WHATSAPP_VIALIDADES_URBANAS_TEMPLATE_LANGUAGE', 'es_MX'),
            'template_layout' => env('WHATSAPP_VIALIDADES_URBANAS_TEMPLATE_LAYOUT', 'diario'),
            'template_chunk_chars' => (int) env('WHATSAPP_VIALIDADES_URBANAS_TEMPLATE_CHUNK_CHARS', 850),
            'text_chunk_chars' => (int) env('WHATSAPP_VIALIDADES_URBANAS_TEXT_CHUNK_CHARS', 3900),
            'incluir_novedades' => filter_var(env('WHATSAPP_VIALIDADES_URBANAS_INCLUIR_NOVEDADES', false), FILTER_VALIDATE_BOOLEAN),
            'firma_cargo' => env('WHATSAPP_VIALIDADES_URBANAS_FIRMA_CARGO', 'SUBDIRECTOR DE PROTECCIÓN EN VIALIDADES URBANAS'),
            'firma_nombre' => env('WHATSAPP_VIALIDADES_URBANAS_FIRMA_NOMBRE'),
        ],

        'carreteras_guardianes' => [
            'to' => env('WHATSAPP_CARRETERAS_GUARDIANES_TO'),
            'template_layout' => env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_LAYOUT', 'tres_partes'),
            'template_part_1' => env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_PARTE_1', 'carreteras_guardianes_consolidado_p1'),
            'template_part_2' => env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_PARTE_2', 'carreteras_guardianes_consolidado_p2'),
            'template_part_3' => env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_PARTE_3', 'carreteras_guardianes_consolidado_p3'),
            'block_template' => env('WHATSAPP_CARRETERAS_GUARDIANES_BLOCK_TEMPLATE', env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE', 'carreteras_guardianes_consolidado_bloque')),
            'template_language' => env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_LANGUAGE', 'es_MX'),
            'template_chunk_chars' => (int) env('WHATSAPP_CARRETERAS_GUARDIANES_TEMPLATE_CHUNK_CHARS', 850),
            'text_chunk_chars' => (int) env('WHATSAPP_CARRETERAS_GUARDIANES_TEXT_CHUNK_CHARS', 3900),
            'unidad_id' => (int) env('WHATSAPP_CARRETERAS_GUARDIANES_UNIDAD_ID', 4),
            'rango_campo' => env('WHATSAPP_CARRETERAS_GUARDIANES_RANGO_CAMPO', 'created_at'),
            'destacamento' => env('WHATSAPP_CARRETERAS_GUARDIANES_DESTACAMENTO', 'MORELIA'),
            'descripcion_general' => env('WHATSAPP_CARRETERAS_GUARDIANES_DESCRIPCION_GENERAL', 'EN TRAMOS CARRETEROS DE LOS MUNICIPIOS: (Aeropuerto, Zinapécuaro, Queréndaro, Indaparapeo, Charo y Morelia La Cinta Texticuitzeo).'),
        ],

        'oficios' => [
            'terminos_to' => env('WHATSAPP_OFICIOS_TERMINOS_TO', env('WHATSAPP_SINIESTROS_TO')),
            'terminos_template' => env('WHATSAPP_OFICIOS_TERMINOS_TEMPLATE'),
            'terminos_template_language' => env('WHATSAPP_OFICIOS_TERMINOS_TEMPLATE_LANGUAGE', 'es_MX'),
        ],

        'licencias_puntos' => [
            'enabled' => filter_var(env('WHATSAPP_LICENCIAS_PUNTOS_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
            'notify_deduccion' => filter_var(env('WHATSAPP_LICENCIAS_PUNTOS_NOTIFY_DEDUCCION', true), FILTER_VALIDATE_BOOLEAN),
            'notify_agotamiento' => filter_var(env('WHATSAPP_LICENCIAS_PUNTOS_NOTIFY_AGOTAMIENTO', true), FILTER_VALIDATE_BOOLEAN),
            'deduccion_template' => env('WHATSAPP_LICENCIAS_PUNTOS_DEDUCCION_TEMPLATE', 'licencia_puntos_descuento'),
            'agotamiento_template' => env('WHATSAPP_LICENCIAS_PUNTOS_AGOTAMIENTO_TEMPLATE', 'licencia_puntos_agotamiento'),
            'template_language' => env('WHATSAPP_LICENCIAS_PUNTOS_TEMPLATE_LANGUAGE', 'es_MX'),
        ],

        'todas_unidades' => [
            'to' => env('WHATSAPP_TODAS_UNIDADES_TO'),
            'template' => env('WHATSAPP_TODAS_UNIDADES_TEMPLATE', 'reporte_todas_unidades_diario'),
            'two_part_template_1' => env('WHATSAPP_TODAS_UNIDADES_TEMPLATE_PARTE_1', 'reporte_todas_unidades_parte_1'),
            'two_part_template_2' => env('WHATSAPP_TODAS_UNIDADES_TEMPLATE_PARTE_2', 'reporte_todas_unidades_parte_2'),
            'block_template' => env('WHATSAPP_TODAS_UNIDADES_BLOCK_TEMPLATE', 'reporte_todas_unidades_bloque'),
            'template_layout' => env('WHATSAPP_TODAS_UNIDADES_TEMPLATE_LAYOUT', 'dos_partes'),
            'template_language' => env('WHATSAPP_TODAS_UNIDADES_TEMPLATE_LANGUAGE', 'es_MX'),
            'template_body_max_chars' => (int) env('WHATSAPP_TODAS_UNIDADES_TEMPLATE_BODY_MAX_CHARS', 1024),
            'template_chunk_chars' => (int) env('WHATSAPP_TODAS_UNIDADES_TEMPLATE_CHUNK_CHARS', 850),
            'text_chunk_chars' => (int) env('WHATSAPP_TODAS_UNIDADES_TEXT_CHUNK_CHARS', 3900),
            'two_part_send_delay_seconds' => (int) env('WHATSAPP_TODAS_UNIDADES_TWO_PART_SEND_DELAY_SECONDS', 2),
        ],

        'delegaciones' => [
            'alertas_to' => env('WHATSAPP_DELEGACIONES_ALERTAS_TO'),
            'cortes_to' => env('WHATSAPP_DELEGACIONES_CORTES_TO', env('WHATSAPP_DELEGACIONES_ALERTAS_TO')),
            'cortes_template' => env('WHATSAPP_DELEGACIONES_CORTES_TEMPLATE', 'delegaciones_corte_aseguramientos_v1'),
            'cortes_template_language' => env('WHATSAPP_DELEGACIONES_CORTES_TEMPLATE_LANGUAGE', 'es_MX'),
            'cortes_schedule_times' => array_values(array_filter(array_map('trim', explode(',', env('WHATSAPP_DELEGACIONES_CORTES_SCHEDULE_TIMES', '15:00,20:00,22:00'))))),
            'incompletos_template' => env('WHATSAPP_DELEGACIONES_INCOMPLETOS_TEMPLATE', 'alerta_hecho_incompleto_delegaciones'),
            'incompletos_min_hours' => (int) env('WHATSAPP_DELEGACIONES_INCOMPLETOS_MIN_HOURS', 3),
            'incompletos_lookback_days' => (int) env('WHATSAPP_DELEGACIONES_INCOMPLETOS_LOOKBACK_DAYS', 3),
            'incompletos_notify_delegados' => filter_var(env('WHATSAPP_DELEGACIONES_INCOMPLETOS_NOTIFY_DELEGADOS', true), FILTER_VALIDATE_BOOLEAN),
            'incompletos_delegados_from_users' => filter_var(env('WHATSAPP_DELEGACIONES_INCOMPLETOS_DELEGADOS_FROM_USERS', true), FILTER_VALIDATE_BOOLEAN),
            'incompletos_delegado_roles' => env('WHATSAPP_DELEGACIONES_INCOMPLETOS_DELEGADO_ROLES', 'Delegado'),
            'incompletos_delegados_to' => env('WHATSAPP_DELEGACIONES_INCOMPLETOS_DELEGADOS_TO'),
        ],
    ],

    'openai' => [
        'key' => env('OPENAI_API_KEY'),
    ],

    'azure_storage' => [
        'account_name' => env('AZURE_STORAGE_NAME'),
        'account_key' => env('AZURE_STORAGE_KEY'),
        'url' => env('AZURE_STORAGE_URL'),
        'oficios_container' => env('AZURE_STORAGE_OFICIOS_CONTAINER', 'oficios'),
        'oficios_enabled' => env('AZURE_STORAGE_OFICIOS_ENABLED',
            env('FILESYSTEM_DISK') === 'azure' || env('FILESYSTEM_DRIVER') === 'azure'
        ),
        'documentos_container' => env('AZURE_STORAGE_DOCUMENTOS_CONTAINER', 'documentos'),
        'documentos_enabled' => env('AZURE_STORAGE_DOCUMENTOS_ENABLED',
            env('AZURE_STORAGE_OFICIOS_ENABLED',
                env('FILESYSTEM_DISK') === 'azure' || env('FILESYSTEM_DRIVER') === 'azure'
            )
        ),
        'croquis_container' => env('AZURE_STORAGE_CROQUIS_CONTAINER', 'croquis'),
        'croquis_enabled' => env('AZURE_STORAGE_CROQUIS_ENABLED',
            env('FILESYSTEM_DISK') === 'azure' || env('FILESYSTEM_DRIVER') === 'azure'
        ),
        'fotos_container' => env('AZURE_STORAGE_FOTOS_CONTAINER', 'fotos'),
        'fotos_enabled' => env('AZURE_STORAGE_FOTOS_ENABLED',
            env('FILESYSTEM_DISK') === 'azure' || env('FILESYSTEM_DRIVER') === 'azure'
        ),
    ],
];
