<?php

return [
    'daily_move_percent' => (float) env('STOCK_NUDGE_DAILY_MOVE_PERCENT', 10),
    'volume_spike_multiple' => (float) env('STOCK_NUDGE_VOLUME_SPIKE_MULTIPLE', 2),
    'max_quote_age_minutes' => (int) env('STOCK_NUDGE_MAX_QUOTE_AGE_MINUTES', 15),
    'market_expiry_minutes' => (int) env('STOCK_NUDGE_MARKET_EXPIRY_MINUTES', 30),
    'repeat_cooldown_days' => (int) env('STOCK_NUDGE_REPEAT_COOLDOWN_DAYS', 5),
    'record_extension_percent' => (float) env('STOCK_NUDGE_RECORD_EXTENSION_PERCENT', 1),
    'market_schedule' => env('STOCK_NUDGE_MARKET_SCHEDULE', '*/5 * * * *'),
    'eod_schedule' => env('STOCK_NUDGE_EOD_SCHEDULE', '15 18 * * 1-5'),
];
