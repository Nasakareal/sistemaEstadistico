<?php

return [
    /*
    | La integracion permanece cerrada hasta que se habilite expresamente.
    | Las credenciales son exclusivas para comunicacion servidor a servidor;
    | nunca deben incluirse en la aplicacion Flutter.
    */
    'enabled' => filter_var(env('SIERSE_API_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    // La autenticacion puede probarse sin habilitar aun el intercambio de expedientes.
    'data_exchange_enabled' => filter_var(env('SIERSE_API_DATA_EXCHANGE_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'require_mtls' => filter_var(env('SIERSE_API_REQUIRE_MTLS', false), FILTER_VALIDATE_BOOLEAN),

    'clock_skew_seconds' => (int) env('SIERSE_API_CLOCK_SKEW_SECONDS', 300),
    'nonce_ttl_seconds' => (int) env('SIERSE_API_NONCE_TTL_SECONDS', 600),

    'clients' => [
        env('SIERSE_API_CLIENT_ID', 'galvan') => [
            'name' => env('SIERSE_API_CLIENT_NAME', 'Galvan'),
            'grua_name' => env('SIERSE_API_GRUA_NAME', 'GALVAN'),
            'key_id' => env('SIERSE_API_KEY_ID', ''),
            'secret' => env('SIERSE_API_CLIENT_SECRET', ''),
            'secret_expires_at' => env('SIERSE_API_SECRET_EXPIRES_AT', ''),
            'allowed_ips' => array_values(array_filter(array_map(
                'trim',
                explode(',', env('SIERSE_API_ALLOWED_IPS', ''))
            ))),
            'certificate_fingerprints' => array_values(array_filter(array_map(
                'trim',
                explode(',', env('SIERSE_API_CERTIFICATE_FINGERPRINTS', ''))
            ))),
        ],
    ],
];
