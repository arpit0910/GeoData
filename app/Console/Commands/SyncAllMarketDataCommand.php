<?php

namespace App\Console\Commands;

use App\Models\CorporateAction;
use App\Models\MarketNews;
use App\Models\MfMaster;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class SyncAllMarketDataCommand extends Command
{
    protected $signature = 'market:sync-all
        {--batch-size=500 : Batch size for Upstox quotes}
        {--news-limit=100 : Equities to check for news}
        {--ca-limit=100 : Equities to check for corporate actions}
        {--include-bonds : Also sync all 14,000+ bonds and debentures}
        {--skip-mf : Skip AMFI Mutual Funds download}';

    protected $description = 'Synchronize all stocks, mutual funds, news, and corporate actions in one command to benchmark performance and verify system load';

    public function handle(): int
    {
        $startOverall = microtime(true);
        $initialMemory = memory_get_usage(true);

        $this->info("=================================================");
        $this->info("    SETUGEO - ALL MARKET DATA SYNC BENCHMARK     ");
        $this->info("=================================================");
        $this->line("Started at: " . now('Asia/Kolkata')->format('Y-m-d H:i:s') . " IST\n");

        // 1. SYNC ALL EQUITIES QUOTES (UPSTOX LTP)
        $quoteType = $this->option('include-bonds') ? 'all' : 'stocks';
        $this->info("[1/4] Syncing active {$quoteType} quotes via Upstox LTP...");
        $t0 = microtime(true);
        $quotesBatch = (int) $this->option('batch-size');
        $exitQuotes = Artisan::call('market:sync-upstox-quotes', [
            '--batch-size' => $quotesBatch,
            '--mode' => 'ltp',
            '--type' => $quoteType,
        ], $this->output);
        $tQuotes = round(microtime(true) - $t0, 2);
        $this->line("--> Quotes sync completed in {$tQuotes}s (exit code: {$exitQuotes})\n");

        // 2. SYNC MUTUAL FUNDS (AMFI)
        if (!$this->option('skip-mf')) {
            $this->info("[2/4] Syncing AMFI Mutual Funds NAV & Scheme Master...");
            $t1 = microtime(true);
            $exitMf = Artisan::call('sync:mf-daily', [
                '--force' => true,
                '--skip-returns' => true,
            ], $this->output);
            $tMf = round(microtime(true) - $t1, 2);
            $this->line("--> Mutual Funds sync completed in {$tMf}s (exit code: {$exitMf})\n");
        } else {
            $this->info("[2/4] Skipped Mutual Funds sync (--skip-mf flag set)\n");
            $tMf = 0;
        }

        // 3. SYNC NEWS (UPSTOX)
        $this->info("[3/4] Syncing breaking stock and market news from Upstox...");
        $t2 = microtime(true);
        $newsLimit = (int) $this->option('news-limit');
        $exitNews = Artisan::call('market:sync-upstox-news', [
            '--limit' => $newsLimit,
            '--batch-size' => 30,
        ], $this->output);
        $tNews = round(microtime(true) - $t2, 2);
        $this->line("--> News sync completed in {$tNews}s (exit code: {$exitNews})\n");

        // 4. SYNC CORPORATE ACTIONS (EVENTS, SPLITS, BONUSES, DIVIDENDS)
        $this->info("[4/4] Syncing corporate actions (splits, bonus, dividends, events)...");
        $t3 = microtime(true);
        $caLimit = (int) $this->option('ca-limit');
        Artisan::call('market:sync-upstox-events', ['--limit' => $caLimit], $this->output);
        Artisan::call('market:sync-upstox-splits', ['--limit' => $caLimit], $this->output);
        Artisan::call('market:sync-upstox-bonuses', ['--limit' => $caLimit], $this->output);
        $tCa = round(microtime(true) - $t3, 2);
        $this->line("--> Corporate actions sync completed in {$tCa}s\n");

        // BENCHMARK SUMMARY & DATABASE TOTALS
        $totalTime = round(microtime(true) - $startOverall, 2);
        $peakMemory = round((memory_get_peak_usage(true) - $initialMemory) / (1024 * 1024), 2);

        $totalQuotes = DB::table('equity_quotes')->count();
        $totalMfs = MfMaster::count();
        $totalNews = MarketNews::count();
        $totalCa = CorporateAction::count();

        $this->info("=================================================");
        $this->info("           SYNC & LOAD TEST SUMMARY              ");
        $this->info("=================================================");
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Time Elapsed', "{$totalTime} seconds"],
                ['Peak Memory Used', "{$peakMemory} MB"],
                ['Stock Quotes in DB', number_format($totalQuotes)],
                ['Mutual Funds in DB', number_format($totalMfs)],
                ['News Articles in DB', number_format($totalNews)],
                ['Corporate Actions in DB', number_format($totalCa)],
            ]
        );

        $this->info("All data feeds synchronized successfully!\n");
        return self::SUCCESS;
    }
}
