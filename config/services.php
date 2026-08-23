<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'clamav' => [
        'host' => env('CLAMAV_HOST', 'localhost'),
        'port' => (int) env('CLAMAV_PORT', 3310),
        'timeout' => (int) env('CLAMAV_TIMEOUT', 30),
        'skip_when_unavailable' => (bool) env('CLAMAV_SKIP_WHEN_UNAVAILABLE', true),
    ],

    'geocoding' => [
        'provider' => env('GEOCODING_PROVIDER', 'google'),
        'api_key' => env('GEOCODING_API_KEY'),
    ],

    'dlt' => [
        'base_url' => env('DLT_BASE_URL', 'https://checkout.dxp.dtic.com.ph'),
        'client_id' => env('DLT_CLIENT_ID'),
        'client_secret' => env('DLT_CLIENT_SECRET'),
        'merchant_account_id' => env('DLT_MERCHANT_ACCOUNT_ID'),
        'service_code' => env('DLT_SERVICE_CODE', 'APN-COLLECTION'),
    ],

];
