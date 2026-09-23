<?php

return [
    'base_url' => env('OPENWA_BASE_URL', 'https://wa.inotive.my.id/api'),
    'api_key' => env('OPENWA_API_KEY'),
    'timeout' => (int) env('OPENWA_TIMEOUT', 20),
];
