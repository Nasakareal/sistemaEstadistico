<?php

return [
    'enabled' => filter_var(env('PDF_COMPRESSION_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
    'required' => filter_var(env('PDF_COMPRESSION_REQUIRED', false), FILTER_VALIDATE_BOOLEAN),
    'binary' => env('PDF_COMPRESSION_BINARY'),
    'preset' => env('PDF_COMPRESSION_PRESET', 'ebook'),
    'min_bytes' => (int) env('PDF_COMPRESSION_MIN_BYTES', 1048576),
    'min_savings_percent' => (float) env('PDF_COMPRESSION_MIN_SAVINGS_PERCENT', 5),
    'timeout' => (int) env('PDF_COMPRESSION_TIMEOUT', 180),
    'max_upload_kb' => (int) env('PDF_COMPRESSION_MAX_UPLOAD_KB', 51200),
    'skip_signed' => filter_var(env('PDF_COMPRESSION_SKIP_SIGNED', true), FILTER_VALIDATE_BOOLEAN),
];
