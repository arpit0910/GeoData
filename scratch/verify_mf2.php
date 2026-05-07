<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Row distribution by year
$byYear = DB::table('mutual_fund_prices')
    ->selectRaw("strftime('%Y', nav_date) as yr, COUNT(*) as cnt, COUNT(DISTINCT isin) as isins")
    ->groupByRaw("strftime('%Y', nav_date)")
    ->orderBy('yr')
    ->get();

echo "Rows by year:\n";
foreach ($byYear as $r) {
    echo "  {$r->yr}: {$r->cnt} rows across {$r->isins} ISINs\n";
}

// ISINs with only 1 row (just today's daily sync — no backfill)
$singleRow = DB::table('mutual_fund_prices')
    ->selectRaw('isin, COUNT(*) as cnt')
    ->groupBy('isin')
    ->havingRaw('cnt = 1')
    ->count();

echo "\nISINs with only 1 NAV row (daily-only, no history): {$singleRow}\n";

// Date range in DB
echo 'Earliest nav_date: ' . DB::table('mutual_fund_prices')->min('nav_date') . "\n";
echo 'Latest nav_date:   ' . DB::table('mutual_fund_prices')->max('nav_date') . "\n";

// What fraction of today's ISINs have enough history for each return period
$latest = DB::table('mutual_fund_prices')->max('nav_date');
$total  = DB::table('mutual_fund_prices')->where('nav_date', $latest)->count();

$checks = [
    'chg_1d (need 1 day back)'  => ['col' => 'chg_1d', 'cutoff' => date('Y-m-d', strtotime($latest . ' -2 days'))],
    'chg_1m (need ~30 days back)' => ['col' => 'chg_1m', 'cutoff' => date('Y-m-d', strtotime($latest . ' -40 days'))],
    'chg_1y (need ~1 yr back)'  => ['col' => 'chg_1y', 'cutoff' => date('Y-m-d', strtotime($latest . ' -13 months'))],
    'chg_3y (need ~3 yr back)'  => ['col' => 'chg_3y', 'cutoff' => date('Y-m-d', strtotime($latest . ' -3 years -1 month'))],
];

echo "\nReturn column coverage for latest date ({$latest}) — {$total} ISINs:\n";
foreach ($checks as $label => $meta) {
    $populated = DB::table('mutual_fund_prices')
        ->where('nav_date', $latest)
        ->whereNotNull($meta['col'])
        ->count();
    $pct = $total > 0 ? round($populated / $total * 100, 1) : 0;
    echo "  {$label}: {$populated}/{$total} ({$pct}%)\n";
}
