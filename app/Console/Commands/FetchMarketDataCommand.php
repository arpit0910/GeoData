<?php

namespace App\Console\Commands;

use App\Events\StockPriceUpdated;
use App\Models\Equity;
use App\Services\EquityQuoteService;
use App\Services\MarketDataService;
use Illuminate\Console\Command;
use Throwable;

class FetchMarketDataCommand extends Command
{
    protected $signature = 'market:fetch-live
        {symbols?* : Optional Yahoo symbols mapped to the equity master}
        {--isin=* : Fetch only these ISINs}
        {--allow-partial : Return success when a bulk run saves at least one quote}';
    protected $description = 'Fetch and persist latest equity quotes by ISIN and exchange.';

    public function handle(MarketDataService $market, EquityQuoteService $quotes): int
    {
        $symbols = $market->normalizeSymbols($this->argument('symbols'));
        $isins = $market->normalizeSymbols($this->option('isin'));
        $query = Equity::query()->where('is_active', true);
        if ($isins) {
            $query->whereIn('isin', $isins);
        }
        $stored = $failed = $attempted = 0;
        $matchedSymbols = $matchedIsins = $targets = [];
        foreach ($query->lazyById(200) as $equity) {
            foreach ($quotes->symbols($equity) as $exchange => $symbol) {
                if ($symbols && !in_array($symbol, $symbols, true)) {
                    continue;
                }
                $matchedSymbols[] = $symbol;
                $matchedIsins[] = $equity->isin;
                $attempted++;
                $targets[] = ['isin' => $equity->isin, 'exchange' => $exchange, 'symbol' => $symbol];
            }
        }

        $liveQuotes = $market->getQuotes(array_column($targets, 'symbol'));
        $reportedFailures = 0;
        foreach ($targets as $target) {
            $quote = $liveQuotes[$target['symbol']] ?? [];
            try {
                if (!$quotes->store($target['isin'], $target['exchange'], $quote)) {
                    $failed++;
                    if ($reportedFailures++ < 20) {
                        $this->warn("No fresh quote for {$target['isin']} ({$target['symbol']}); saved value retained.");
                    }
                    continue;
                }
                $stored++;
            } catch (Throwable $e) {
                $failed++;
                if ($reportedFailures++ < 20) {
                    $this->error("{$target['isin']} ({$target['symbol']}): {$e->getMessage()}");
                }
                continue;
            }
            try {
                event(new StockPriceUpdated($target['symbol'], array_merge($quote, [
                    'isin' => $target['isin'],
                    'exchange' => $target['exchange'],
                ])));
            } catch (Throwable $e) {
                $this->warn("Quote saved but broadcast failed for {$target['symbol']}: {$e->getMessage()}");
            }
        }
        if ($reportedFailures > 20) {
            $this->warn(($reportedFailures - 20).' additional quote failures omitted from the console output.');
        }
        foreach (array_diff($symbols, $matchedSymbols) as $symbol) {
            $this->error("Symbol not mapped to an active equity: {$symbol}");
            $failed++;
        }
        foreach (array_diff($isins, $matchedIsins) as $isin) {
            $this->error("No eligible equity symbols for ISIN: {$isin}");
            $failed++;
        }
        $this->info("Attempted: {$attempted}; saved: {$stored}; failed: {$failed}.");
        if ($attempted === 0 || ($failed > 0 && (!$this->option('allow-partial') || $stored === 0))) {
            return self::FAILURE;
        }
        return self::SUCCESS;
    }
}
