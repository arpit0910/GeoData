<?php

namespace App\Console\Commands;

use App\Events\StockPriceUpdated;
use App\Services\MarketDataService;
use Illuminate\Console\Command;

class FetchMarketDataCommand extends Command
{
    /**
     * Optional array input:
     * php artisan market:fetch-live AAPL TSLA ^GSPC
     */
    protected $signature = 'market:fetch-live {symbols?* : Optional Yahoo Finance symbols}';

    protected $description = 'Fetch live stock and index prices from Yahoo Finance and broadcast updates.';

    public function __construct(private readonly MarketDataService $marketDataService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $symbols = $this->marketDataService->normalizeSymbols(
            $this->argument('symbols'),
            ['AAPL', '^GSPC', 'TSLA', 'MSFT']
        );

        $this->info('Fetching market data for: '.implode(', ', $symbols));

        foreach ($symbols as $symbol) {
            $priceData = $this->marketDataService->getQuote($symbol);

            event(new StockPriceUpdated($symbol, $priceData));

            $this->line(sprintf(
                '[%s] %s => %s (%s%%) [%s]',
                now()->format('H:i:s'),
                $symbol,
                $priceData['price'] ?? 'n/a',
                $priceData['dp'] ?? 'n/a',
                $priceData['source'] ?? 'unknown'
            ));
        }

        $this->info('Live market fetch cycle completed.');

        return self::SUCCESS;
    }
}
