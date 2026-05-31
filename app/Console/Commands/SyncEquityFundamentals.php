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

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($equities as $equity) {
            $symbol = urlencode($equity->nse_symbol);
            $url = "https://www.screener.in/company/{$symbol}/consolidated/";

            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                ])->timeout(10)->withoutVerifying()->get($url);

                if ($response->status() === 404) {
                    // Try standalone if consolidated doesn't exist
                    $url = "https://www.screener.in/company/{$symbol}/";
                    $response = Http::withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
                    ])->timeout(10)->withoutVerifying()->get($url);
                }

                if ($response->successful()) {
                    $html = $response->body();

                    preg_match('/Stock P\/E.*?<span class="number">(.*?)<\/span>/s', $html, $pe);
                    preg_match('/Market Cap.*?<span class="number">(.*?)<\/span>/s', $html, $mc);
                    preg_match('/Book Value.*?<span class="number">(.*?)<\/span>/s', $html, $bv);
                    preg_match('/Dividend Yield.*?<span class="number">(.*?)<\/span>/s', $html, $dy);
                    preg_match('/Current Price.*?<span class="number">(.*?)<\/span>/s', $html, $price);

                    $pe_val = isset($pe[1]) ? (float)str_replace(',', '', $pe[1]) : null;
                    $mc_val = isset($mc[1]) ? (float)str_replace(',', '', $mc[1]) : null; // In Crores
                    $bv_val = isset($bv[1]) ? (float)str_replace(',', '', $bv[1]) : null;
                    $dy_val = isset($dy[1]) ? (float)str_replace(',', '', $dy[1]) / 100 : null; // Convert to decimal
                    $price_val = isset($price[1]) ? (float)str_replace(',', '', $price[1]) : null;

                    $pb_val = ($bv_val > 0 && $price_val > 0) ? round($price_val / $bv_val, 2) : null;
                    $eps_val = ($pe_val > 0 && $price_val > 0) ? round($price_val / $pe_val, 2) : null;

                    // Convert Market Cap from Crores to standard raw number for consistency (if it exists)
                    $raw_mc = $mc_val !== null ? $mc_val * 10000000 : null;

                    // Find the correct price record to update by exclusively checking ISIN and traded_date
                    $priceRecord = EquityPrice::where('isin', $equity->isin)
                        ->where('traded_date', $date)
                        ->first();

                    if ($priceRecord) {
                        $priceRecord->update([
                            'market_cap'     => $raw_mc ?? $priceRecord->market_cap,
                            'pe_ratio'       => $pe_val ?? $priceRecord->pe_ratio,
                            'pb_ratio'       => $pb_val ?? $priceRecord->pb_ratio,
                            'dividend_yield' => $dy_val ?? $priceRecord->dividend_yield,
                            'eps'            => $eps_val ?? $priceRecord->eps,
                        ]);

                        // Update the static market cap on Equity model as well for quick access
                        if ($raw_mc !== null) {
                            $equity->update(['market_cap' => $raw_mc]);
                        }
                        $updatedCount++;
                    }
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
            }

            $bar->advance();
            // Sleep to respect rate limits on Screener.in and prevent IP bans
            usleep(250000); // 250ms
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Fundamentals Sync Complete!");
        $this->info("Successfully Updated: {$updatedCount}");
        if ($failedCount > 0) {
            $this->error("Failed to fetch: {$failedCount} (likely invalid symbol on Screener)");
        }
    }
}
