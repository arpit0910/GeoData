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
    protected $signature = 'market:fetch-live {symbols?* : Optional Yahoo symbols mapped to the equity master} {--isin=* : Fetch only these ISINs}';
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
        $matchedSymbols = $matchedIsins = [];
        foreach ($query->lazyById(200) as $equity) {
            foreach ($quotes->symbols($equity) as $exchange => $symbol) {
                if ($symbols && !in_array($symbol, $symbols, true)) {
                    continue;
                }
                $matchedSymbols[] = $symbol;
                $matchedIsins[] = $equity->isin;
                $attempted++;
                try {
                    $quote = $market->getQuote($symbol);
                    if (!$quotes->store($equity->isin, $exchange, $quote)) {
                        $failed++;
                        $this->warn("No fresh quote for {$equity->isin} ({$symbol}); saved value retained.");
                        continue;
                    }
                    $stored++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->error("{$equity->isin} ({$symbol}): {$e->getMessage()}");
                    continue;
                }
                try {
                    event(new StockPriceUpdated($symbol, array_merge($quote, ['isin' => $equity->isin, 'exchange' => $exchange])));
                } catch (Throwable $e) {
                    $this->warn("Quote saved but broadcast failed for {$symbol}: {$e->getMessage()}");
                }
            }
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
        return $failed > 0 || $attempted === 0 ? self::FAILURE : self::SUCCESS;
    }
}
