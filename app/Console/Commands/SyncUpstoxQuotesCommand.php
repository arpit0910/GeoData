<?php

namespace App\Console\Commands;

use App\Events\StockPriceUpdated;
use App\Models\Equity;
use App\Services\EquityQuoteService;
use App\Services\UpstoxMarketDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class SyncUpstoxQuotesCommand extends Command
{
    protected $signature = 'market:sync-upstox-quotes
        {--batch-size=500 : Instruments per Upstox request, from 1 to 500}
        {--limit= : Maximum number of stocks to sync in this run}
        {--mode=ltp : Quote API mode to use (ltp or full)}
        {--type=equities : Instrument category: equities (stocks only), bonds (bonds/debentures only), or all}
        {--exchange= : Fetch only NSE or BSE}
        {--isin=* : Fetch only these ISINs; all mapped stocks are fetched when omitted}
        {--once-daily : Skip instruments that have already been synced today}';

    protected $description = 'Fetch and save latest Upstox quotes/LTP for mapped equities or bonds (constantly for equities, once daily after close for bonds)';

    public function handle(UpstoxMarketDataService $upstox, EquityQuoteService $quoteStore): int
    {
        $batchSize = (int) $this->option('batch-size');
        if ($batchSize < 1 || $batchSize > UpstoxMarketDataService::MAX_INSTRUMENTS) {
            $this->error('The --batch-size value must be between 1 and 500.');
            return self::INVALID;
        }

        if (trim((string) config('market_data.upstox.access_token')) === '') {
            $this->error('UPSTOX_ACCESS_TOKEN is not configured.');
            return self::FAILURE;
        }

        $limit = $this->option('limit') ? (int) $this->option('limit') : null;
        $mode = strtolower(trim((string) ($this->option('mode') ?: 'ltp')));
        $type = strtolower(trim((string) ($this->option('type') ?: 'equities')));
        $onceDaily = (bool) $this->option('once-daily');
        $exchangeFilter = strtoupper(trim((string) ($this->option('exchange') ?: '')));
        if ($exchangeFilter !== '' && !in_array($exchangeFilter, ['NSE', 'BSE'], true)) {
            $this->error('The --exchange value must be NSE or BSE.');
            return self::INVALID;
        }

        $isins = collect($this->option('isin'))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))
            ->filter()
            ->unique()
            ->values();

        $query = $this->baseQuery($exchangeFilter, $type);
        if ($isins->isNotEmpty()) {
            $query->whereIn('isin', $isins->all());
        }

        if ($onceDaily) {
            $todayStartUtc = now('Asia/Kolkata')->startOfDay()->utc();
            $alreadySyncedIsins = DB::table('equity_quotes')
                ->where('fetched_at', '>=', $todayStartUtc)
                ->pluck('isin')
                ->all();
            if (!empty($alreadySyncedIsins)) {
                $query->whereNotIn('isin', $alreadySyncedIsins);
            }
        }

        if ($limit !== null && $limit > 0) {
            $query->limit($limit);
        }

        if (!(clone $query)->exists()) {
            if ($onceDaily) {
                $this->info('All requested instruments have already been synchronized today (once-daily mode).');
                return self::SUCCESS;
            }
            $this->error("No active instruments matching type '{$type}' have an Upstox instrument key.");
            return self::FAILURE;
        }

        $batches = 0;
        $requested = 0;
        $saved = 0;
        $missing = 0;
        $failedBatches = 0;

        $processChunk = function ($equities) use (
            $upstox,
            $quoteStore,
            $mode,
            $exchangeFilter,
            $batchSize,
            &$batches,
            &$requested,
            &$saved,
            &$missing,
            &$failedBatches
        ) {
            $targets = $equities->flatMap(fn (Equity $equity) => $this->targets($equity, $exchangeFilter))->values();
            if ($targets->isEmpty()) {
                return;
            }

            foreach ($targets->chunk($batchSize) as $targetBatch) {
                $batches++;
                $requested += $targetBatch->count();

                try {
                    $instrumentKeys = $targetBatch->pluck('instrument_key')->all();
                    $quotes = in_array($mode, ['full', 'quotes', 'quote'], true)
                        ? $upstox->quotes($instrumentKeys)
                        : $upstox->ltp($instrumentKeys);
                } catch (Throwable $exception) {
                    $failedBatches++;
                    $missing += $targetBatch->count();
                    $this->error("Upstox batch {$batches} failed: {$exception->getMessage()}");
                    continue;
                }

                foreach ($targetBatch as $target) {
                    $quote = $quotes[$target->instrument_key] ?? null;
                    if ($quote === null) {
                        $missing++;
                        continue;
                    }

                    if ($quote['symbol'] === '') {
                        $quote['symbol'] = $target->symbol ?: $target->isin;
                    }

                    try {
                        if (!$quoteStore->store($target->isin, $target->exchange, $quote)) {
                            $missing++;
                            continue;
                        }
                        $saved++;
                    } catch (Throwable $exception) {
                        $missing++;
                        $this->warn("Quote could not be saved for {$target->isin}: {$exception->getMessage()}");
                        continue;
                    }

                    try {
                        event(new StockPriceUpdated($quote['symbol'], array_merge($quote, [
                            'isin' => $target->isin,
                            'exchange' => $target->exchange,
                        ])));
                    } catch (Throwable $exception) {
                        $this->warn("Quote saved but broadcast failed for {$target->isin}: {$exception->getMessage()}");
                    }
                }
            }
        };

        if ($limit !== null && $limit > 0) {
            $equities = $query->get();
            $processChunk($equities);
        } else {
            $query->chunkById($batchSize, $processChunk, 'id');
        }

        $this->info(
            "Batches: {$batches}; requested: {$requested}; saved: {$saved}; "
            ."missing/failed: {$missing}; failed batches: {$failedBatches}."
        );

        return ($saved > 0 || $requested === 0) && $failedBatches === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function baseQuery(string $exchangeFilter = '', string $type = 'equities')
    {
        $query = Equity::query()
            ->where('is_active', true)
            ->where(function ($query) use ($exchangeFilter) {
                if ($exchangeFilter === 'NSE') {
                    $query->whereNotNull('upstox_nse_instrument_key')
                        ->where('upstox_nse_instrument_key', '<>', '');
                } elseif ($exchangeFilter === 'BSE') {
                    $query->whereNotNull('upstox_bse_instrument_key')
                        ->where('upstox_bse_instrument_key', '<>', '');
                } else {
                    $query->where(function ($query) {
                        $query->whereNotNull('upstox_nse_instrument_key')
                            ->where('upstox_nse_instrument_key', '<>', '');
                    })->orWhere(function ($query) {
                        $query->whereNotNull('upstox_bse_instrument_key')
                            ->where('upstox_bse_instrument_key', '<>', '');
                    });
                }
            })
            ->select([
                'id', 'isin', 'series', 'nse_symbol', 'bse_symbol',
                'upstox_nse_instrument_key', 'upstox_bse_instrument_key',
            ]);

        $normalizedType = strtolower(trim($type));
        if (in_array($normalizedType, ['equities', 'stocks', 'eq'], true)) {
            $query->whereIn('series', ['EQ', 'BE', 'SM', 'BZ']);
        } elseif (in_array($normalizedType, ['bonds', 'debt', 'fixed-income'], true)) {
            $query->whereNotIn('series', ['EQ', 'BE', 'SM', 'BZ']);
        }

        return $query;
    }

    /**
     * @return array<int, object>
     */
    private function targets(Equity $equity, string $exchangeFilter = ''): array
    {
        $targets = [];

        if (($exchangeFilter === '' || $exchangeFilter === 'NSE') && !empty($equity->upstox_nse_instrument_key)) {
            $targets[] = (object) [
                'id' => $equity->id,
                'isin' => $equity->isin,
                'exchange' => 'NSE',
                'symbol' => $equity->nse_symbol,
                'instrument_key' => $equity->upstox_nse_instrument_key,
            ];
        }

        if (($exchangeFilter === '' || $exchangeFilter === 'BSE') && !empty($equity->upstox_bse_instrument_key)) {
            $targets[] = (object) [
                'id' => $equity->id,
                'isin' => $equity->isin,
                'exchange' => 'BSE',
                'symbol' => $equity->bse_symbol,
                'instrument_key' => $equity->upstox_bse_instrument_key,
            ];
        }

        return $targets;
    }
}
