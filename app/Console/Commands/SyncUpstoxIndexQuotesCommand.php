<?php

namespace App\Console\Commands;

use App\Models\Index;
use App\Services\IndexQuoteService;
use App\Services\UpstoxMarketDataService;
use App\Services\UpstoxTokenManager;
use Illuminate\Console\Command;
use Throwable;

class SyncUpstoxIndexQuotesCommand extends Command
{
    protected $signature = 'market:sync-upstox-index-quotes
        {--batch-size=500 : Instruments per Upstox request}
        {--mode=ltp : ltp or full quote mode}
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

        $indices = Index::query()
            ->whereNotNull('upstox_instrument_key')
            ->where('upstox_instrument_key', '<>', '')
            ->get(['index_code', 'upstox_instrument_key']);

        if ($indices->isEmpty()) {
            $this->error('No indices have an Upstox instrument key. Run equities:sync-upstox-instruments first.');
            return self::FAILURE;
        }

        try {
            $tokens->accessToken();
        } catch (Throwable $exception) {
            $this->error('Upstox index synchronization stopped: '.$exception->getMessage());
            return self::FAILURE;
        }

        $saved = 0;
        $missing = 0;
        $failedBatches = 0;

        foreach ($indices->chunk($batchSize) as $batchNumber => $batch) {
            try {
                $instrumentKeys = $batch->pluck('upstox_instrument_key')->all();
                $quotes = $mode === 'full' ? $upstox->quotes($instrumentKeys) : $upstox->ltp($instrumentKeys);
            } catch (Throwable $exception) {
                $failedBatches++;
                $missing += $batch->count();
                $this->error('Upstox index batch '.($batchNumber + 1).' failed: '.$exception->getMessage());
                continue;
            }

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

        $this->info("Requested: {$indices->count()}; saved: {$saved}; missing/failed: {$missing}.");

        if ($this->option('snapshot-eod') && $failedBatches === 0) {
            $snapshots = $quoteStore->snapshotEndOfDay();
            $this->info("EOD index price rows saved: {$snapshots}.");
        }

        return $failedBatches === 0 && $saved > 0 ? self::SUCCESS : self::FAILURE;
    }
}
