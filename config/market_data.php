<?php

return [
    // Optional CA bundle for installations without a system PHP trust store.
    'ca_bundle' => env('MARKET_DATA_CA_BUNDLE'),

    'upstox' => [
        'access_token' => env('UPSTOX_ACCESS_TOKEN', env('UPSTOX_TOKEN')),
        'quote_url' => env('UPSTOX_QUOTE_URL', 'https://api.upstox.com/v3/market-quote/quotes'),
        'ltp_url' => env('UPSTOX_LTP_URL', 'https://api.upstox.com/v3/market-quote/ltp'),
        'corporate_actions_url' => env('UPSTOX_CORPORATE_ACTIONS_URL', 'https://api.upstox.com/v2/fundamentals'),
        'news_url' => env('UPSTOX_NEWS_URL', 'https://api.upstox.com/v2/news'),
    ],
];
