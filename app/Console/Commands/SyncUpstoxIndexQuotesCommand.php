<?php

namespace App\Console\Commands;

use App\Models\Index;
use App\Services\IndexQuoteService;
use App\Services\UpstoxMarketDataService;
use App\Services\UpstoxTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Throwable;

class SyncUpstoxIndexQuotesCommand extends Command
{
    protected $signature = 'market:sync-upstox-index-quotes
        {--batch-size=500 : Instruments per Upstox request}
        {--mode=ltp : ltp or full quote mode}
        {--quoted-only : Refresh only indices that have previously returned a valid quote}
        {--snapshot-eod : Upsert the final quotes into indices_prices}';

    protected $description = 'Fetch and store live NSE/BSE index quotes from Upstox';

    public function handle(UpstoxMarketDataService $upstox, UpstoxTokenManager $tokens, IndexQuoteService $quoteStore): int
    {
        $batchSize = (int) $this->option('batch-size');
        if ($batchSize < 1 || $batchSize > UpstoxMarketDataService::MAX_INSTRUMENTS) {
            $this->error('The --batch-size value must be between 1 and 500.');
            return self::INVALID;
        }
        $mode = strtolower(trim((string) $this->option('mode')));
        if (!in_array($mode, ['ltp', 'full'], true)) {
            $this->error('The --mode value must be ltp or full.');
            return self::INVALID;
        }

        // Keep newly configured sector indices eligible for their first live quote.
        app(\App\Services\UpstoxInstrumentSyncService::class)->ensureIndexInstrumentKeys();

        $quotedOnly = (bool) $this->option('quoted-only');
        $hasQuotedIndices = Index::query()->whereHas('quote')->exists();
        if ($quotedOnly && ! $hasQuotedIndices) {
            $this->info('No previously quoted indices found; running the initial index bootstrap.');
        }

        $indexQuery = Index::query()
            ->whereNotNull('upstox_instrument_key')
            ->where('upstox_instrument_key', '<>', '');

        if ($quotedOnly && $hasQuotedIndices) {
            $configuredBenchmarks = array_values(array_unique([
                (string) config('stock_nudges.market_benchmark'),
                ...array_values(config('stock_nudges.sector_indices', [])),
            ]));
            $indexQuery->where(function ($query) use ($configuredBenchmarks) {
                $query->whereHas('quote')
                    ->orWhereIn('index_code', $configuredBenchmarks);
            });
        }

        $indices = $indexQuery->get(['index_code', 'upstox_instrument_key']);

        if ($indices->isEmpty()) {
            $this->info('Initializing index instrument keys...');
            app(\App\Services\UpstoxInstrumentSyncService::class)->ensureIndexInstrumentKeys();

            $indices = Index::query()
                ->whereNotNull('upstox_instrument_key')
                ->where('upstox_instrument_key', '<>', '')
                ->when($quotedOnly && $hasQuotedIndices, fn ($query) => $query->whereHas('quote'))
                ->get(['index_code', 'upstox_instrument_key']);
        }

        if ($indices->isEmpty()) {
            $this->error('No indices have a market instrument key configured.');
            return self::FAILURE;
        }

        try {
            $tokens->accessToken();
        } catch (Throwable $exception) {
            $this->error('Index synchronization stopped: '.$exception->getMessage());
            return self::FAILURE;
        }

        $saved = 0;
        $missing = 0;
        $failedBatches = 0;
        $invalidKeys = 0;

        foreach ($indices->chunk($batchSize) as $batchNumber => $batch) {
            $quotes = $this->fetchQuotes(
                $batch->values(),
                $mode,
                $upstox,
                $batchNumber + 1,
                $failedBatches,
                $invalidKeys,
            );

            foreach ($batch as $index) {
                $quote = $quotes[$index->upstox_instrument_key] ?? null;
                if ($quote === null) {
                    $missing++;
                    continue;
                }

                if ($quoteStore->store($index->index_code, $quote)) {
                    $saved++;
                } else {
                    $missing++;
                }
            }
        }

        $this->info("Requested: {$indices->count()}; saved: {$saved}; missing/failed: {$missing}; invalid keys skipped: {$invalidKeys}.");

        if ($this->option('snapshot-eod') && $failedBatches === 0) {
            $snapshots = $quoteStore->snapshotEndOfDay();
            $this->info("EOD index price rows saved: {$snapshots}.");
        }

        return $failedBatches === 0 && $saved > 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Upstox rejects an entire request when any one instrument key is stale.
     * Split only those validation failures so valid indices still get updated.
     *
     * @param Collection<int, Index> $indices
     * @return array<string, array<string, mixed>>
     */
    private function fetchQuotes(
        Collection $indices,
        string $mode,
        UpstoxMarketDataService $upstox,
        int $batchNumber,
        int &$failedBatches,
        int &$invalidKeys,
    ): array {
        try {
            $instrumentKeys = $indices->pluck('upstox_instrument_key')->all();

            return $mode === 'full'
                ? $upstox->quotes($instrumentKeys)
                : $upstox->ltp($instrumentKeys);
        } catch (Throwable $exception) {
            if ($this->isInvalidInstrumentError($exception)) {
                if ($indices->count() > 1) {
                    $splitAt = (int) ceil($indices->count() / 2);

                    return array_replace(
                        $this->fetchQuotes($indices->take($splitAt)->values(), $mode, $upstox, $batchNumber, $failedBatches, $invalidKeys),
                        $this->fetchQuotes($indices->slice($splitAt)->values(), $mode, $upstox, $batchNumber, $failedBatches, $invalidKeys),
                    );
                }

                $index = $indices->first();
                $invalidKeys++;
                $this->warn("Skipped invalid Upstox key for {$index->index_code}: {$index->upstox_instrument_key}");

                return [];
            }

            $failedBatches++;
            $this->error("Index batch {$batchNumber} failed: {$exception->getMessage()}");

            return [];
        }
    }

    private function isInvalidInstrumentError(Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'http 400')
            && (str_contains($message, 'instrument_key')
                || str_contains($message, 'symbol')
                || str_contains($message, 'invalid'));
    }
}
