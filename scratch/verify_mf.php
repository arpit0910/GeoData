<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// 1. Totals
$totalIsins    = DB::table('mutual_funds')->count();
$withPrices    = DB::table('mutual_fund_prices')->distinct()->count('isin');
$backfilled    = DB::table('mutual_fund_prices')->where('nav_date', '<=', '2023-04-06')->distinct()->count('isin');
$totalPriceRows= DB::table('mutual_fund_prices')->count();

// 2. ISINs with NO price rows at all
$noPrices = DB::table('mutual_funds')
    ->leftJoin('mutual_fund_prices', 'mutual_funds.isin', '=', 'mutual_fund_prices.isin')
    ->whereNull('mutual_fund_prices.isin')
    ->count();

// 3. ISINs that have recent price rows but no early history (need backfill)
$allIsins        = DB::table('mutual_fund_prices')->distinct()->pluck('isin');
$backfilledIsins = DB::table('mutual_fund_prices')->where('nav_date', '<=', '2023-04-06')->distinct()->pluck('isin');
$needsBackfill   = $allIsins->diff($backfilledIsins)->count();

// 4. Return column completeness for today's date
$todayDate    = DB::table('mutual_fund_prices')->max('nav_date');
$todayTotal   = DB::table('mutual_fund_prices')->where('nav_date', $todayDate)->count();
$todayWith1d  = DB::table('mutual_fund_prices')->where('nav_date', $todayDate)->whereNotNull('chg_1d')->count();
$todayWith1y  = DB::table('mutual_fund_prices')->where('nav_date', $todayDate)->whereNotNull('chg_1y')->count();
$todayWith3y  = DB::table('mutual_fund_prices')->where('nav_date', $todayDate)->whereNotNull('chg_3y')->count();

// 5. Sample ISIN — check date range and a few return values
$sample = DB::table('mutual_fund_prices')
    ->where('nav_date', '<=', '2023-04-06')
    ->select('isin')
    ->first();
$sampleIsin = $sample ? $sample->isin : null;

$sampleStats = null;
if ($sampleIsin) {
    $sampleStats = [
        'isin'      => $sampleIsin,
        'min_date'  => DB::table('mutual_fund_prices')->where('isin', $sampleIsin)->min('nav_date'),
        'max_date'  => DB::table('mutual_fund_prices')->where('isin', $sampleIsin)->max('nav_date'),
        'row_count' => DB::table('mutual_fund_prices')->where('isin', $sampleIsin)->count(),
        'latest'    => DB::table('mutual_fund_prices')
            ->where('isin', $sampleIsin)
            ->orderByDesc('nav_date')
            ->select('nav_date', 'nav', 'chg_1d', 'chg_1m', 'chg_1y', 'chg_3y')
            ->first(),
    ];
}

echo "=== MF Data Health Check ===\n\n";
echo "mutual_funds table:\n";
echo "  Total ISINs: {$totalIsins}\n";
echo "  ISINs with NO price rows at all: {$noPrices}\n\n";

echo "mutual_fund_prices table:\n";
echo "  Total price rows: {$totalPriceRows}\n";
echo "  ISINs with any price data: {$withPrices}\n";
echo "  ISINs backfilled (rows from Apr 2023): {$backfilled}\n";
echo "  ISINs with recent data but NOT backfilled: {$needsBackfill}\n\n";

echo "Return column coverage for latest NAV date ({$todayDate}) — {$todayTotal} funds:\n";
echo "  chg_1d populated: {$todayWith1d} / {$todayTotal}\n";
echo "  chg_1y populated: {$todayWith1y} / {$todayTotal}\n";
echo "  chg_3y populated: {$todayWith3y} / {$todayTotal}  (null until ~Apr 2026 by design)\n\n";

if ($sampleStats) {
    echo "Sample ISIN: {$sampleStats['isin']}\n";
    echo "  Date range: {$sampleStats['min_date']} → {$sampleStats['max_date']}\n";
    echo "  Row count:  {$sampleStats['row_count']}\n";
    $l = $sampleStats['latest'];
    echo "  Latest row: date={$l->nav_date} nav={$l->nav} chg_1d={$l->chg_1d} chg_1m={$l->chg_1m} chg_1y={$l->chg_1y} chg_3y={$l->chg_3y}\n";
}
