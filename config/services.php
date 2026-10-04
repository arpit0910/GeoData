<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'ocr' => [
        'url' => env('OCR_SERVICE_URL', 'http://localhost:8001'),
        'key' => env('OCR_SERVICE_API_KEY', 'your-secret-api-key-change-this'),
        'timeout' => (int) env('OCR_SERVICE_TIMEOUT', 30),
        'connect_timeout' => (int) env('OCR_SERVICE_CONNECT_TIMEOUT', 5),
    ],

    'financial_api' => [
        'overview_endpoint' => env('FINANCIAL_API_OVERVIEW_ENDPOINT'),
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_NEWS_MODEL', 'gemini-3.8-flash'),
        'fallback_models' => env('GEMINI_NEWS_FALLBACK_MODELS', 'gemini-3.6-flash,gemini-3.5-flash'),
        'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta'),
        'temperature' => (float) env('GEMINI_NEWS_TEMPERATURE', 0.1),
        'timeout' => (int) env('GEMINI_API_TIMEOUT', 60),
        'ca_bundle' => env('GEMINI_CA_BUNDLE', env('MARKET_DATA_CA_BUNDLE')),
    ],

    'subscriptions' => [
        // Keep paid checkout off until the live Razorpay keys are configured.
        'purchases_enabled' => (bool) env('SUBSCRIPTION_PURCHASES_ENABLED', false),
    ],

];
