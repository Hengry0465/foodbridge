<?php

return [
    'identity_url' => env('IDENTITY_SERVICE_URL'),
    'donation_url' => env('DONATION_SERVICE_URL'),
    'module_id' => env('MODULE_ID', 'module-3'),
    'module_api_key' => env('MODULE_API_KEY'),
    'allowed_clock_skew_seconds' => (int) env('MODULE_CLOCK_SKEW_SECONDS', 300),
];
