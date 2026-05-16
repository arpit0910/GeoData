<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SyncEquityFundamentals extends Command
{
    protected $signature = 'equities:sync-fundamentals {date?}';
    protected $description = 'Fetch daily fundamentals (PE, PB, Market Cap, etc.) from Yahoo Finance and store them in equity_prices.';

    public function handle()
    {
        $this->info("Starting Daily Fundamentals Sync...");

        // Determine the date to sync for. Defaults to the latest available traded date in the database.
        $date = $this->argument('date');
        if (!$date) {
            $date = EquityPrice::max('traded_date');
        }

        if (!$date) {
            $this->error("No existing prices found to determine the latest traded_date. Please sync prices first or provide a date.");
            return;
        }

        $this->info("Syncing fundamentals for traded_date: {$date}");

        // Get all active equities that have an NSE symbol
        $equities = Equity::where('is_active', true)
            ->whereNotNull('nse_symbol')
            ->where('nse_symbol', '!=', '')
            ->get();

        $total = $equities->count();
        $this->info("Found {$total} active equities to sync.");

        $updatedCount = 0;
        $failedCount = 0;

        // Process in chunks of 50 to avoid hitting Yahoo Finance URL length limits
        $chunks = $equities->chunk(50);
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($chunks as $chunk) {
            $symbols = $chunk->map(function ($equity) {
                // Yahoo finance uses .NS for NSE
                // Handle special characters if needed, but mostly direct match
                $cleanSymbol = str_replace(['&', '-'], ['', ''], $equity->nse_symbol);
                // A better approach is using urlencode, but Yahoo prefers exact ticker.
                return urlencode($equity->nse_symbol) . '.NS';
            })->toArray();

            $symbolString = implode(',', $symbols);
            $url = "https://query2.finance.yahoo.com/v7/finance/quote?symbols={$symbolString}";

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ])->timeout(30)->withoutVerifying()->get($url);

                if ($response->successful()) {
                    $data = $response->json();
                    $results = $data['quoteResponse']['result'] ?? [];

                    foreach ($results as $result) {
                        $symbol = str_replace('.NS', '', $result['symbol'] ?? '');
                        // some symbols might be URL encoded in the response, some not
                        $symbol = urldecode($symbol);

                        $equity = $chunk->first(function ($item) use ($symbol) {
                            return $item->nse_symbol === $symbol || urlencode($item->nse_symbol) === $symbol;
                        });

                        if ($equity) {
                            // Find the correct price record to update by exclusively checking ISIN and traded_date
                            $priceQuery = EquityPrice::where('isin', $equity->isin)
                                ->where('traded_date', $date);

                            $price = $priceQuery->first();

                            if ($price) {
                                $price->update([
                                    'outstanding_shares' => $result['sharesOutstanding'] ?? $price->outstanding_shares,
                                    'market_cap'         => $result['marketCap'] ?? $price->market_cap,
                                    'pe_ratio'           => $result['trailingPE'] ?? $price->pe_ratio,
                                    'pb_ratio'           => $result['priceToBook'] ?? $price->pb_ratio,
                                    'dividend_yield'     => isset($result['dividendYield']) ? $result['dividendYield'] / 100 : $price->dividend_yield,
                                    'eps'                => $result['trailingEps'] ?? $price->eps,
                                ]);

                                // Update the static market cap on Equity model as well for quick access
                                if (isset($result['marketCap'])) {
                                    $equity->update([
                                        'market_cap' => $result['marketCap']
                                    ]);
                                }
                                $updatedCount++;
                            }
                        }
                    }
                } else {
                    if ($response->status() === 401) {
                        $this->warn("\nYahoo Finance API returned 401 Unauthorized. Yahoo has recently restricted free API access. You may need to use a premium API (like EODHD or FMP) or implement advanced cookie/crumb scraping.");
                        return; // Exit early to prevent 100s of failed requests
                    }
                    Log::warning("Yahoo Finance API failed with status " . $response->status());
                    $failedCount += $chunk->count();
                }
            } catch (\Exception $e) {
                Log::error("Yahoo Finance API exception: " . $e->getMessage());
                $failedCount += $chunk->count();
            }

            $bar->advance($chunk->count());
            // small delay to prevent rate limiting
            usleep(200000);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Fundamentals Sync Complete!");
        $this->info("Successfully Updated: {$updatedCount}");
        if ($failedCount > 0) {
            $this->error("Failed to fetch: {$failedCount}");
        }
    }
}
