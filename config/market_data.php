<?php

return [
    // Optional CA bundle for installations without a system PHP trust store.
    'ca_bundle' => env('MARKET_DATA_CA_BUNDLE'),

    'upstox' => [
        'access_token' => env('UPSTOX_ACCESS_TOKEN'),
        'quote_url' => env('UPSTOX_QUOTE_URL', 'https://api.upstox.com/v3/market-quote/quotes'),
    ],
];
