<?php

namespace App\Services;

use App\Models\Equity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EquityQuoteService
{
    public function symbols(Equity $equity): array
    {
        $symbols = [];
        foreach (['NSE' => ['nse_symbol', '.NS'], 'BSE' => ['bse_symbol', '.BO']] as $exchange => [$field, $suffix]) {
            $symbol = strtoupper(trim((string) $equity->$field));
            if ($symbol !== '') {
                $symbols[$exchange] = str_ends_with($symbol, $suffix) ? $symbol : $symbol.$suffix;
            }
        }
        return $symbols;
    }

    public function store(string $isin, string $exchange, array $quote): bool
    {
        if (($quote['source'] ?? null) !== 'live' || !is_numeric($quote['price'] ?? null) || empty($quote['quoted_at'])) {
            return false;
        }

        $key = ['isin' => $isin, 'exchange' => $exchange];
        $values = [
            'symbol' => $quote['symbol'],
            'price' => $quote['price'],
            'quoted_at' => Carbon::parse($quote['quoted_at'])->utc()->format('Y-m-d H:i:s'),
            'fetched_at' => Carbon::parse($quote['fetched_at'])->utc()->format('Y-m-d H:i:s'),
            'payload' => json_encode(array_merge($quote, $key), JSON_THROW_ON_ERROR),
        ];
        // Insert once, then only advance the snapshot when the provider time advances.
        DB::table('equity_quotes')->insertOrIgnore(array_merge($key, $values));
        DB::table('equity_quotes')->where($key)->where('quoted_at', '<=', $values['quoted_at'])->update($values);
        return true;
    }

    public function latest(string $isin): array
    {
        return DB::table('equity_quotes')->where('isin', $isin)->orderBy('exchange')->get()
            ->map(function ($row) {
                $quote = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
                $quote['source'] = 'database';
                $quote['age_seconds'] = max(0, now()->timestamp - Carbon::parse($quote['quoted_at'])->timestamp);
                $quote['is_stale'] = $quote['age_seconds'] > 900;
                return $quote;
            })->all();
    }
}
