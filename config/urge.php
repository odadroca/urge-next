<?php

return [
    'max_include_depth' => (int) env('URGE_MAX_INCLUDE_DEPTH', 10),
    'curl_ssl_verify'   => env('CURL_SSL_VERIFY', true),
];
