<?php

namespace App\Console\Commands;

use App\Events\StockPriceUpdated;
use App\Models\Equity;
use App\Services\EquityQuoteService;
use App\Services\UpstoxMarketDataService;
use Illuminate\Console\Command;
use Throwable;

class SyncUpstoxQuotesCommand extends Command
{
    protected $signature = 'market:sync-upstox-quotes
        {--batch-size=500 : Instruments per Upstox request, from 1 to 500}
        {--isin=* : Fetch only these ISINs; all mapped stocks are fetched when omitted}';

    protected $description = 'Fetch and save latest Upstox quotes for every mapped active stock';

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

        $isins = collect($this->option('isin'))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))
            ->filter()
            ->unique()
            ->values();
        $query = $this->baseQuery();
        if ($isins->isNotEmpty()) {
            $query->whereIn('isin', $isins->all());
        }

        if (!(clone $query)->exists()) {
            $this->error('No active equities have an Upstox instrument key.');
            return self::FAILURE;
        }

        $batches = 0;
        $requested = 0;
        $saved = 0;
        $missing = 0;
        $failedBatches = 0;

        $query->chunkById($batchSize, function ($equities) use (
            $upstox,
            $quoteStore,
            &$batches,
            &$requested,
            &$saved,
            &$missing,
            &$failedBatches
        ) {
            $targets = $equities->map(fn (Equity $equity) => $this->target($equity));
            $batches++;
            $requested += $targets->count();

            try {
                $quotes = $upstox->quotes($targets->pluck('instrument_key')->all());
            } catch (Throwable $exception) {
                $failedBatches++;
                $missing += $targets->count();
                $this->error("Upstox batch {$batches} failed: {$exception->getMessage()}");
                return;
            }

            foreach ($targets as $target) {
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
        }, 'id');

        $this->info(
            "Batches: {$batches}; requested: {$requested}; saved: {$saved}; "
            ."missing/failed: {$missing}; failed batches: {$failedBatches}."
        );

        return $saved > 0 && $missing === 0 && $failedBatches === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function baseQuery()
    {
        return Equity::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNotNull('upstox_nse_instrument_key')
                    ->orWhereNotNull('upstox_bse_instrument_key');
            })
            ->select([
                'id', 'isin', 'nse_symbol', 'bse_symbol',
                'upstox_nse_instrument_key', 'upstox_bse_instrument_key',
            ]);
    }

    private function target(Equity $equity): object
    {
        $useNse = !empty($equity->upstox_nse_instrument_key);

        return (object) [
            'id' => $equity->id,
            'isin' => $equity->isin,
            'exchange' => $useNse ? 'NSE' : 'BSE',
            'symbol' => $useNse ? $equity->nse_symbol : $equity->bse_symbol,
            'instrument_key' => $useNse
                ? $equity->upstox_nse_instrument_key
                : $equity->upstox_bse_instrument_key,
        ];
    }
}
