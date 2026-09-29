<?php

return [
    'enabled' => filter_var(env('SECURITY_LOGGING_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'retention_days' => max(7, (int) env('SECURITY_LOG_RETENTION_DAYS', 90)),
    'review_ip_threshold' => max(5, (int) env('SECURITY_LOG_REVIEW_IP_THRESHOLD', 20)),
    'probe_limit_per_hour' => max(3, (int) env('SECURITY_PROBE_LIMIT_PER_HOUR', 10)),
    'trusted_proxies' => env('TRUSTED_PROXIES', ''),
];
