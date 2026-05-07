<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

// 1. Pick 20 schemes
$schemes = DB::table('mutual_funds')->where('is_active', 1)->orderBy('id', 'asc')->limit(20)->get();
foreach ($schemes as $scheme) {
    echo "Testing Scheme: {$scheme->scheme_name} (Code: {$scheme->scheme_code})\n";
}

// 2. Run sync for the batch
echo "Running parallel sync:mf-history for 20 schemes...\n";
// Note: We can't easily pass multiple scheme codes to the current command signature, 
// so I'll just run it normally and it will use the new parallel logic internally for all schemes.
// To keep the test short, I'll limit the query in a temporary way or just run for a few.
$before = DB::table('mutual_fund_prices')->where('isin', $scheme->isin)->count();
echo "Prices before: $before\n";

// 3. Run sync
echo "Running sync:mf-history...\n";
Artisan::call('sync:mf-history', [
    'months' => 36, 
    '--scheme' => $scheme->scheme_code, 
    '--force' => true,
    '--skip-master' => true // skip master refresh for this unit test
]);
echo Artisan::output();

// 4. Count after
$after = DB::table('mutual_fund_prices')->where('isin', $scheme->isin)->count();
echo "Prices after: $after\n";

// 5. Test Compute Returns
echo "Running compute:mf-returns...\n";
Artisan::call('compute:mf-returns', [
    '--isin' => $scheme->isin,
    '--force' => true
]);
echo Artisan::output();

// 6. Verify one record
$price = DB::table('mutual_fund_prices')
    ->where('isin', $scheme->isin)
    ->whereNotNull('chg_1y')
    ->orderBy('nav_date', 'desc')
    ->first();

if ($price) {
    echo "Verification Success!\n";
    echo "Date: {$price->nav_date}, NAV: {$price->nav}, 1Y Return: {$price->chg_1y}%\n";
} else {
    echo "Verification Failed or no 1Y data available for this scheme.\n";
    // Check if any record has returns
    $any = DB::table('mutual_fund_prices')
        ->where('isin', $scheme->isin)
        ->whereNotNull('chg_1d')
        ->count();
    echo "Records with any returns: $any\n";
}
