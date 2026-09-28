<?php

namespace App\Services;

use App\Models\Equity;
use App\Models\EquityPrice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EquityQuoteService
{
    public function symbols(Equity $equity): array
    {
        foreach (['NSE' => ['nse_symbol', '.NS'], 'BSE' => ['bse_symbol', '.BO']] as $exchange => [$field, $suffix]) {
            $symbol = strtoupper(trim((string) $equity->$field));
            if ($symbol !== '') {
                return [$exchange => str_ends_with($symbol, $suffix) ? $symbol : $symbol.$suffix];
            }
        }

        return [];
    }

    public function store(string $isin, string $exchange, array $quote): bool
    {
        if (($quote['source'] ?? null) !== 'live' || !is_numeric($quote['price'] ?? null) || empty($quote['quoted_at'])) {
            return false;
        }

        $key = ['isin' => strtoupper(trim($isin))];
        $values = [
            'exchange' => strtoupper(trim($exchange)),
            'symbol' => $quote['symbol'],
            'price' => $quote['price'],
            'quoted_at' => Carbon::parse($quote['quoted_at'])->utc()->format('Y-m-d H:i:s'),
            'fetched_at' => Carbon::parse($quote['fetched_at'])->utc()->format('Y-m-d H:i:s'),
            'payload' => json_encode(array_merge($quote, $key), JSON_THROW_ON_ERROR),
        ];
        // One mutable latest-quote row per ISIN. Older provider responses must
        // never replace a newer snapshot.
        DB::table('equity_quotes')->insertOrIgnore(array_merge($key, $values));
        DB::table('equity_quotes')->where($key)->where('quoted_at', '<=', $values['quoted_at'])->update($values);
        return true;
    }

    public function latest(string $isin): array
    {
        return DB::table('equity_quotes')->where('isin', $isin)->get()
            ->map(function ($row) {
                $quote = json_decode($row->payload, true, 512, JSON_THROW_ON_ERROR);
                $quote['source'] = 'database';
                $quote['age_seconds'] = max(0, now()->timestamp - Carbon::parse($quote['quoted_at'])->timestamp);
                $quote['is_stale'] = $quote['age_seconds'] > 900;
                return $quote;
            })->all();
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
                    $quotedDate = Carbon::parse($quote->quoted_at, 'UTC')
                        ->timezone('Asia/Kolkata')
                        ->toDateString();

                    // Do not turn Friday's stale quote into a weekend/holiday row.
                    if ($quotedDate !== $dateString) {
                        continue;
                    }

                    $payload = json_decode((string) $quote->payload, true);
                    $marketData = is_array($payload) ? ($payload['market_data'] ?? []) : [];
                    $ohlc = is_array($marketData) ? ($marketData['ohlc'] ?? []) : [];
                    $prefix = strtoupper((string) $quote->exchange) === 'BSE' ? 'bse' : 'nse';
                    $price = (float) $quote->price;

                    $equityPrice = EquityPrice::query()
                        ->where('isin', $quote->isin)
                        ->whereDate('traded_date', $dateString)
                        ->first() ?? new EquityPrice([
                            'isin' => $quote->isin,
                            'traded_date' => $dateString,
                        ]);

                    $equityPrice->fill(array_filter([
                            'equity_id' => $quote->equity_id,
                            "{$prefix}_open" => $this->numericOrNull($ohlc['open'] ?? null),
                            "{$prefix}_high" => $this->numericOrNull($ohlc['high'] ?? null),
                            "{$prefix}_low" => $this->numericOrNull($ohlc['low'] ?? null),
                            "{$prefix}_close" => $price,
                            "{$prefix}_last" => $price,
                            "{$prefix}_prev_close" => $this->numericOrNull($payload['previous_close'] ?? null),
                            "{$prefix}_volume" => $this->integerOrNull($marketData['volume'] ?? $ohlc['volume'] ?? null),
                            "{$prefix}_avg_price" => $this->numericOrNull($marketData['average_price'] ?? null),
                        ], fn ($value) => $value !== null));
                    $equityPrice->save();
                    $saved++;
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
