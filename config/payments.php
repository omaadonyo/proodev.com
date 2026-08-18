<?php

return [

    'currency' => env('PAYMENTS_CURRENCY', env('BILLING_CURRENCY', 'USD')),

    /*
    |--------------------------------------------------------------------------
    | Payment methods
    |--------------------------------------------------------------------------
    |
    | Each method maps to a gateway driver. Settings stored in the
    | payment_method_settings table (managed by admins) take precedence over
    | these static defaults, which act as a fallback for local development.
    |
    */

    'methods' => [

        'bank' => [
            'enabled' => env('PAYMENTS_BANK_ENABLED', true),
            'bank_name' => env('PAYMENTS_BANK_NAME', ''),
            'account_name' => env('PAYMENTS_BANK_ACCOUNT_NAME', ''),
            'account_number' => env('PAYMENTS_BANK_ACCOUNT_NUMBER', ''),
            'bank_code' => env('PAYMENTS_BANK_CODE', ''),
            'reference_prefix' => 'PDV',
        ],

        'flutterwave' => [
            'enabled' => env('PAYMENTS_FLUTTERWAVE_ENABLED', true),
            'base_url' => env('FLUTTERWAVE_BASE_URL', 'https://api.flutterwave.com/v3'),
            'public_key' => env('FLUTTERWAVE_PUBLIC_KEY', ''),
            'secret_key' => env('FLUTTERWAVE_SECRET_KEY', ''),
            'webhook_secret' => env('FLUTTERWAVE_WEBHOOK_SECRET', ''),
        ],

        'pesapal' => [
            'enabled' => env('PAYMENTS_PESAPAL_ENABLED', true),
            'base_url' => env('PESAPAL_BASE_URL', 'https://pay.pesapal.com/v3'),
            'consumer_key' => env('PESAPAL_CONSUMER_KEY', ''),
            'consumer_secret' => env('PESAPAL_CONSUMER_SECRET', ''),
            'currency' => env('PESAPAL_CURRENCY', 'KES'),
            'ipn_id' => env('PESAPAL_IPN_ID', ''),
        ],

        'worldremit' => [
            'enabled' => env('PAYMENTS_WORLDREMIT_ENABLED', true),
            'payout_country' => env('PAYMENTS_WORLDREMIT_COUNTRY', 'Uganda'),
            'mobile_money_provider' => env('PAYMENTS_WORLDREMIT_PROVIDER', 'MTN Mobile Money'),
            'mobile_money_number' => env('PAYMENTS_WORLDREMIT_NUMBER', '0786634306'),
            'account_name' => env('PAYMENTS_WORLDREMIT_ACCOUNT_NAME', 'Emmanuel Adonyo'),
            'reference_prefix' => 'WRM',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Return routes
    |--------------------------------------------------------------------------
    */

    'return_url' => env('PAYMENTS_RETURN_URL', '/credits'),
    'notify_url' => env('PAYMENTS_NOTIFY_URL', '/payments/notify'),
];
