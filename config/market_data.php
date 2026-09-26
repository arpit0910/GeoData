<?php

return [
    // Optional CA bundle for installations without a system PHP trust store.
    'ca_bundle' => env('MARKET_DATA_CA_BUNDLE'),

    'upstox' => [
        'access_token' => env('UPSTOX_ACCESS_TOKEN', env('UPSTOX_TOKEN')),
        'client_id' => env('UPSTOX_CLIENT_ID', env('UPSTOX_API_KEY')),
        'client_secret' => env('UPSTOX_CLIENT_SECRET', env('UPSTOX_API_SECRET')),
        'notifier_secret' => env('UPSTOX_NOTIFIER_SECRET')
            ?: hash('sha256', (string) env('APP_KEY').'|upstox-token-notifier'),
        'token_request_url' => env('UPSTOX_TOKEN_REQUEST_URL', 'https://api.upstox.com/v3/login/auth/token/request'),
        'fundamentals_url' => env('UPSTOX_FUNDAMENTALS_URL', 'https://api.upstox.com/v2/fundamentals'),
        'global_instruments_url' => env('UPSTOX_GLOBAL_INSTRUMENTS_URL', 'https://assets.upstox.com/market-quote/instruments/exchange/global.json.gz'),
        'quote_url' => env('UPSTOX_QUOTE_URL', 'https://api.upstox.com/v3/market-quote/quotes'),
        'ltp_url' => env('UPSTOX_LTP_URL', 'https://api.upstox.com/v3/market-quote/ltp'),
        'corporate_actions_url' => env('UPSTOX_CORPORATE_ACTIONS_URL', 'https://api.upstox.com/v2/fundamentals'),
        'news_url' => env('UPSTOX_NEWS_URL', 'https://api.upstox.com/v2/news'),
    ],
];
