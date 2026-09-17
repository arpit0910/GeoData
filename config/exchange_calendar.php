<?php

return [
    'timezone' => 'Asia/Kolkata',
    'segment' => 'equity',
    'verify_tls' => env('EXCHANGE_CALENDAR_VERIFY_TLS', true),
    'ca_bundle' => env('EXCHANGE_CALENDAR_CA_BUNDLE', env('MARKET_DATA_CA_BUNDLE')),
    'nse' => [
        'url' => env('NSE_HOLIDAY_URL', 'https://www.nseindia.com/api/holiday-master?type=trading'),
        'page_url' => 'https://www.nseindia.com/resources/exchange-communication-holidays',
    ],
    'bse' => [
        'url' => env('BSE_HOLIDAY_URL', 'https://www.bseindia.com/static/markets/marketinfo/listholi.aspx/1000'),
        // BSE's generic holiday page is now client-rendered and its initial
        // HTML contains no calendar rows. Prefer the official annual circular
        // when its notice number is known, then fall back to the generic page.
        'year_urls' => [
            2025 => env('BSE_HOLIDAY_URL_2025', 'https://www.bseindia.com/markets/MarketInfo/DispNewNoticesCirculars.aspx?page=20241213-30'),
            2026 => env('BSE_HOLIDAY_URL_2026', 'https://www.bseindia.com/markets/MarketInfo/DispNewNoticesCirculars.aspx?page=20251212-8'),
        ],
        'supplemental_urls' => [
            2026 => array_filter([
                env('BSE_HOLIDAY_UPDATE_URL_2026', 'https://www.bseindia.com/markets/MarketInfo/DispNewNoticesCirculars.aspx?page=20260112-8'),
            ]),
        ],
    ],
];
