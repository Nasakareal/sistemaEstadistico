<?php

$contentSecurityPolicy = implode('; ', [
    "default-src 'self'",
    "base-uri 'self'",
    "form-action 'self'",
    "frame-ancestors 'self'",
    "object-src 'none'",
    "script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.datatables.net https://unpkg.com https://www.tiktok.com https://*.tiktok.com https://*.tiktokcdn.com https://*.ttwstatic.com",
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://cdn.datatables.net https://unpkg.com",
    "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
    "img-src 'self' data: blob: https:",
    "connect-src 'self' https:",
    "frame-src 'self' https://www.tiktok.com https://*.tiktok.com",
    "media-src 'self' blob: https:",
    "worker-src 'self' blob:",
    "manifest-src 'self'",
]);

return [
    // Cambiar temporalmente a true permite observar una incompatibilidad sin
    // bloquear recursos mientras se ajusta la política en producción.
    'csp_report_only' => filter_var(
        env('SECURITY_CSP_REPORT_ONLY', false),
        FILTER_VALIDATE_BOOLEAN
    ),

    'content_security_policy' => env(
        'SECURITY_CONTENT_SECURITY_POLICY',
        $contentSecurityPolicy
    ),
];
