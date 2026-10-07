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

        $exchange = strtoupper(trim($exchange));
        if (!in_array($exchange, ['NSE', 'BSE'], true)) {
            return false;
        }

        $key = ['isin' => strtoupper(trim($isin))];
        $prefix = strtolower($exchange);
        $fetchedAt = Carbon::parse($quote['fetched_at'] ?? now())->utc()->format('Y-m-d H:i:s');
        $quoteTime = Carbon::parse($quote['quoted_at'])->utc();
        $quotedAt = $quoteTime->year >= 2000
            ? $quoteTime->format('Y-m-d H:i:s')
            : $fetchedAt;
        $payload = json_encode(array_merge($quote, $key, ['exchange' => $exchange]), JSON_THROW_ON_ERROR);
        $exchangeValues = [
            "{$prefix}_symbol" => $quote['symbol'],
            "{$prefix}_price" => $quote['price'],
            "{$prefix}_quoted_at" => $quotedAt,
            "{$prefix}_fetched_at" => $fetchedAt,
            "{$prefix}_payload" => $payload,
        ];
        $legacyValues = [
            'exchange' => $exchange,
            'symbol' => $quote['symbol'],
            'price' => $quote['price'],
            'quoted_at' => $quotedAt,
            'fetched_at' => $fetchedAt,
            'payload' => $payload,
        ];

        DB::transaction(function () use ($key, $prefix, $quotedAt, $exchangeValues, $legacyValues) {
            DB::table('equity_quotes')->insertOrIgnore(array_merge($key, $legacyValues, $exchangeValues));

            // NSE and BSE advance independently; a delayed response from one
            // exchange must not overwrite that exchange's newer quote.
            DB::table('equity_quotes')
                ->where($key)
                ->where(function ($query) use ($prefix, $quotedAt) {
                    $query->whereNull("{$prefix}_quoted_at")
                        ->orWhere("{$prefix}_quoted_at", '<=', $quotedAt);
                })
                ->update($exchangeValues);

            // Keep the original columns as a backward-compatible preferred
            // quote: NSE when present, otherwise BSE.
            $row = DB::table('equity_quotes')->where($key)->lockForUpdate()->first();
            $preferred = $row?->nse_price !== null ? 'nse' : 'bse';
            $preferredPayload = json_decode((string) ($row->{$preferred.'_payload'} ?? ''), true);
            $preferredPayload = is_array($preferredPayload) ? $preferredPayload : [];
            DB::table('equity_quotes')->where($key)->update([
                'exchange' => strtoupper($preferred),
                'symbol' => $row->{$preferred.'_symbol'},
                'price' => $row->{$preferred.'_price'},
                'quoted_at' => $row->{$preferred.'_quoted_at'},
                'fetched_at' => $row->{$preferred.'_fetched_at'},
                'payload' => $row->{$preferred.'_payload'},
                'previous_close' => $this->numericOrNull($preferredPayload['previous_close'] ?? null),
                'change' => $this->numericOrNull($preferredPayload['d'] ?? null),
                'change_percent' => $this->numericOrNull($preferredPayload['dp'] ?? null),
                'live_volume' => $this->integerOrNull(
                    $preferredPayload['volume'] ?? data_get($preferredPayload, 'market_data.volume')
                ),
            ]);
        });

        return true;
    }

    public function latest(string $isin): array
    {
        $row = DB::table('equity_quotes')->where('isin', strtoupper(trim($isin)))->first();
        if (!$row) {
            return [];
        }

        return collect(['NSE' => 'nse', 'BSE' => 'bse'])
            ->map(function ($prefix, $exchange) use ($row) {
                $payload = $row->{$prefix.'_payload'} ?? null;
                if (!$payload) {
                    return null;
                }

                $quote = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
                $quote['exchange'] = $exchange;
                $quote['source'] = 'database';
                $quote['age_seconds'] = max(0, now()->timestamp - Carbon::parse($quote['quoted_at'])->timestamp);
                $quote['is_stale'] = $quote['age_seconds'] > 900;
                return $quote;
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Copy today's final latest quote into the daily price history. Re-running
     * this method updates the same ISIN/date row instead of creating duplicates.
     */
    public function snapshotEndOfDay(?Carbon $tradedDate = null): int
    {
        $date = ($tradedDate ?: now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata')->startOfDay();
        $dateString = $date->toDateString();
        $saved = 0;

        DB::table('equity_quotes as q')
            ->join('equities as e', 'e.isin', '=', 'q.isin')
            ->select('q.*', 'e.id as equity_id')
            ->orderBy('q.id')
            ->chunkById(500, function ($quotes) use ($dateString, &$saved) {
                foreach ($quotes as $quote) {
                    $values = [
                        'equity_id' => $quote->equity_id,
                        'isin' => $quote->isin,
                        'traded_date' => $dateString,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                    $hasCurrentQuote = false;

                    foreach (['nse', 'bse'] as $prefix) {
                        $quotedAt = $quote->{$prefix.'_quoted_at'} ?? null;
                        $payloadJson = $quote->{$prefix.'_payload'} ?? null;
                        if (!$quotedAt || !$payloadJson) {
                            continue;
                        }

                        $quotedDate = Carbon::parse($quotedAt, 'UTC')
                            ->timezone('Asia/Kolkata')
                            ->toDateString();
                        if ($quotedDate !== $dateString) {
                            continue;
                        }

                        $payload = json_decode((string) $payloadJson, true);
                        $marketData = is_array($payload) ? ($payload['market_data'] ?? []) : [];
                        $ohlc = is_array($marketData) ? ($marketData['ohlc'] ?? []) : [];
                        $price = (float) $quote->{$prefix.'_price'};

                        $values = array_merge($values, array_filter([
                            "{$prefix}_open" => $this->numericOrNull($ohlc['open'] ?? null),
                            "{$prefix}_high" => $this->numericOrNull($ohlc['high'] ?? null),
                            "{$prefix}_low" => $this->numericOrNull($ohlc['low'] ?? null),
                            "{$prefix}_close" => $price,
                            "{$prefix}_last" => $price,
                            "{$prefix}_prev_close" => $this->numericOrNull($payload['previous_close'] ?? null),
                            "{$prefix}_volume" => $this->integerOrNull($marketData['volume'] ?? $ohlc['volume'] ?? null),
                            "{$prefix}_avg_price" => $this->numericOrNull($marketData['average_price'] ?? null),
                        ], fn ($value) => $value !== null));
                        $hasCurrentQuote = true;
                    }

                    if ($hasCurrentQuote) {
                        // The database has a unique ISIN + traded_date key.
                        // One atomic upsert writes both exchanges into that row
                        // and makes overlapping EOD jobs safe.
                        DB::table('equity_prices')->upsert(
                            [$values],
                            ['isin', 'traded_date'],
                            array_values(array_diff(array_keys($values), ['isin', 'traded_date', 'created_at']))
                        );
                        $saved++;
                    }
                }
            }, 'q.id', 'id');

        return $saved;
    }

    private function numericOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function integerOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
