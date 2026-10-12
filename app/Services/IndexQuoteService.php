<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndexQuoteService
{
    public function store(string $indexCode, array $quote): bool
    {
        if (($quote['source'] ?? null) !== 'live' || !is_numeric($quote['price'] ?? null) || empty($quote['quoted_at'])) {
            return false;
        }

        $key = ['index_code' => strtoupper(trim($indexCode))];
        $quotedAt = Carbon::parse($quote['quoted_at'])->utc()->format('Y-m-d H:i:s');
        $fetchedAt = Carbon::parse($quote['fetched_at'] ?? now())->utc()->format('Y-m-d H:i:s');
        $values = [
            'price' => $quote['price'],
            'previous_close' => $this->numeric($quote['previous_close'] ?? null),
            'change' => $this->numeric($quote['d'] ?? null),
            'change_percent' => $this->numeric($quote['dp'] ?? null),
            'volume' => $this->numeric($quote['volume'] ?? null),
            'quoted_at' => $quotedAt,
            'fetched_at' => $fetchedAt,
            'payload' => json_encode($quote, JSON_THROW_ON_ERROR),
            'updated_at' => now(),
        ];

        DB::transaction(function () use ($key, $values, $quotedAt): void {
            DB::table('index_quotes')->insertOrIgnore($key + $values + ['created_at' => now()]);
            DB::table('index_quotes')->where($key)->where('quoted_at', '<=', $quotedAt)->update($values);
        });

        return true;
    }

    /** Copy the final live quote into the same index/date EOD history row. */
    public function snapshotEndOfDay(?Carbon $tradedDate = null): int
    {
        $date = ($tradedDate ?: now('Asia/Kolkata'))->copy()->timezone('Asia/Kolkata')->toDateString();
        $saved = 0;

        DB::table('index_quotes')->orderBy('id')->chunkById(500, function ($quotes) use ($date, &$saved): void {
            foreach ($quotes as $quote) {
                if (Carbon::parse($quote->quoted_at, 'UTC')->timezone('Asia/Kolkata')->toDateString() !== $date) {
                    continue;
                }

                $payload = json_decode((string) $quote->payload, true);
                $marketData = is_array($payload) ? ($payload['market_data'] ?? []) : [];
                $ohlc = is_array($marketData) ? ($marketData['ohlc'] ?? []) : [];
                $values = array_filter([
                    'index_code' => $quote->index_code,
                    'traded_date' => $date,
                    'open' => $this->numeric($ohlc['open'] ?? null),
                    'high' => $this->numeric($ohlc['high'] ?? null),
                    'low' => $this->numeric($ohlc['low'] ?? null),
                    'close' => $this->numeric($quote->price),
                    'prev_close' => $this->numeric($quote->previous_close),
                    'change_percent' => $this->numeric($quote->change_percent),
                    'volume' => $this->numeric($quote->volume ?? $marketData['volume'] ?? null),
                    'created_at' => now(),
                    'updated_at' => now(),
                ], fn ($value) => $value !== null);

                DB::table('indices_prices')->upsert(
                    [$values],
                    ['index_code', 'traded_date'],
                    array_values(array_diff(array_keys($values), ['index_code', 'traded_date', 'created_at']))
                );
                $saved++;
            }
        });

        return $saved;
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
