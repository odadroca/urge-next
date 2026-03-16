<?php

return [
    'max_include_depth'  => (int) env('URGE_MAX_INCLUDE_DEPTH', 10),
    'curl_ssl_verify'    => env('CURL_SSL_VERIFY', true),

    // API Key settings
    'key_prefix'         => env('URGE_KEY_PREFIX', 'urge_'),
    'key_bytes'          => (int) env('URGE_KEY_BYTES', 31),
    'key_preview_length' => (int) env('URGE_KEY_PREVIEW_LENGTH', 8),

    // API Rate limiting (per key)
    'api_rate_limit'     => (int) env('URGE_API_RATE_LIMIT', 60),
    'api_rate_window'    => (int) env('URGE_API_RATE_WINDOW', 60),
];
