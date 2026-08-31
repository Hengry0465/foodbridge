<?php

return [

    // Not used by the Pickup module's own logic; included only because
    // Illuminate\Mail\MailServiceProvider (needed for notification support
    // in the wider FoodBridge app) expects this config file to exist.
    'default' => env('MAIL_MAILER', 'log'),

    'mailers' => [
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
        'array' => [
            'transport' => 'array',
        ],
    ],

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@foodbridge.test'),
        'name' => env('MAIL_FROM_NAME', 'FoodBridge'),
    ],

];
