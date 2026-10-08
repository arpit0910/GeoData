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

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
        'model' => env('GROQ_NEWS_MODEL', 'openai/gpt-oss-120b'),
        'fallback_models' => env('GROQ_NEWS_FALLBACK_MODELS', 'qwen/qwen3.8-27b,openai/gpt-oss-20b'),
        'model_strategy' => env('GROQ_NEWS_MODEL_STRATEGY', 'round_robin'),
        'endpoint' => env('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions'),
        'temperature' => (float) env('GROQ_NEWS_TEMPERATURE', 0.1),
        'timeout' => (int) env('GROQ_API_TIMEOUT', 60),
        'retry_attempts' => (int) env('GROQ_RETRY_ATTEMPTS', 4),
        'retry_initial_delay_ms' => (int) env('GROQ_RETRY_INITIAL_DELAY_MS', 1000),
        'retry_max_delay_ms' => (int) env('GROQ_RETRY_MAX_DELAY_MS', 8000),
        'retry_jitter_ms' => (int) env('GROQ_RETRY_JITTER_MS', 250),
        'rate_limit_retry_seconds' => (int) env('GROQ_RATE_LIMIT_RETRY_SECONDS', 300),
        'editorial_attempts' => (int) env('GROQ_NEWS_EDITORIAL_ATTEMPTS', 3),
        'ca_bundle' => env('GROQ_CA_BUNDLE', env('MARKET_DATA_CA_BUNDLE')),
    ],

    'subscriptions' => [
        // Keep paid checkout off until the live Razorpay keys are configured.
        'purchases_enabled' => (bool) env('SUBSCRIPTION_PURCHASES_ENABLED', false),
    ],

];
