<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncMfDailyCommand extends Command
{
    protected $signature = 'sync:mf-daily
                            {--force : Bypass the 9 PM–11 PM IST window guard}
                            {--dry-run : Parse and count rows without writing to DB}';

    protected $description = 'Fetch AMFI NAVAll.txt and upsert mutual_funds + mutual_fund_prices';

    // AMFI publishes updated NAVs between 21:00–23:00 IST.
    // Running outside that window risks persisting stale afternoon NAVs.
    private const NAV_WINDOW_START = 21;
    private const NAV_WINDOW_END   = 23;
    private const AMFI_URL         = 'https://www.amfiindia.com/spages/NAVAll.txt';
    private const MASTER_CHUNK     = 500;
    private const NAV_CHUNK        = 1000;

    public function handle(): int
    {
        if (!$this->option('force') && !$this->inNavWindow()) {
            $this->warn('Outside NAV update window (21:00–23:00 IST). Use --force to override.');
            return Command::FAILURE;
        }

        $this->info('Downloading NAVAll.txt from AMFI...');

        try {
            $response = Http::timeout(60)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get(self::AMFI_URL);

            if (!$response->successful()) {
                $this->error('AMFI download failed: HTTP ' . $response->status());
                return Command::FAILURE;
            }
        } catch (\Exception $e) {
            $this->error('AMFI download failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        $this->info('Parsing...');
        [$masterRows, $navRows] = $this->parse($response->body());

        $this->info(sprintf('Parsed %d schemes, %d NAV records.', count($masterRows), count($navRows)));

        if ($this->option('dry-run')) {
            $this->info('[Dry-run] No writes performed.');
            return Command::SUCCESS;
        }

        $this->upsertMaster($masterRows);
        $this->upsertNav($navRows);

        // Compute returns for today's NAV date against existing history
        $navDate = !empty($navRows) ? $navRows[0]['nav_date'] : null;
        if ($navDate) {
            $this->computeReturnsForDate($navDate);
        }

        $this->info('sync:mf-daily complete.');
        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Parsing
    // -------------------------------------------------------------------------

    /**
     * NAVAll.txt format (semicolon-delimited, no quotes):
     *   Scheme Code;ISIN Div Payout/ ISIN Growth;ISIN Div Reinvestment;Scheme Name;Net Asset Value;Date
     *
     * Category header lines look like:
     *   Open Ended Schemes(Equity Scheme - Large Cap Fund)
     * AMC header lines have no semicolons.
     * Data rows always have exactly 6 fields.
     */
    private function parse(string $body): array
    {
        $lines      = explode("\n", str_replace("\r", '', $body));
        $masterRows = [];
        $navRows    = [];

        $currentAmc      = null;
        $currentCategory = null;
        $currentType     = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line === 'Scheme Code;ISIN Div Payout/ ISIN Growth;ISIN Div Reinvestment;Scheme Name;Net Asset Value;Date') {
                continue;
            }

            $fields = explode(';', $line);

            // AMC name line — no semicolons, not a data row
            if (count($fields) === 1) {
                // Detect category lines like "Open Ended Schemes(Equity Scheme - Large Cap Fund)"
                if (preg_match('/Open Ended Schemes\((.+)\)/i', $line, $m)) {
                    [$currentCategory, $currentType] = $this->parseCategory($m[1]);
                } elseif (preg_match('/Close Ended Schemes\((.+)\)/i', $line, $m)) {
                    [$currentCategory, $currentType] = $this->parseCategory($m[1]);
                } elseif (preg_match('/Interval Fund Schemes\((.+)\)/i', $line, $m)) {
                    [$currentCategory, $currentType] = $this->parseCategory($m[1]);
                } else {
                    // AMC name
                    $currentAmc = $line;
                }
                continue;
            }

            if (count($fields) < 6) continue;

            [$schemeCode, $isinGrowth, $isinReinvest, $schemeName, $nav, $navDate] = array_map('trim', $fields);

            // Skip rows with no valid ISIN or non-numeric scheme code
            if (!is_numeric($schemeCode) || strlen($isinGrowth) !== 12) continue;

            // Skip N/A NAVs (NFOs not yet launched, etc.)
            if (!is_numeric($nav) || (float)$nav <= 0) continue;

            $navDateParsed = $this->parseDate($navDate);
            if (!$navDateParsed) continue;

            $masterRows[$isinGrowth] = [
                'isin'          => $isinGrowth,
                'scheme_code'   => $schemeCode,
                'isin_reinvest' => strlen($isinReinvest) === 12 ? $isinReinvest : null,
                'scheme_name'   => substr($schemeName, 0, 300),
                'amc_name'      => $currentAmc,
                'category'      => $currentCategory,
                'sub_category'  => null,
                'type'          => $currentType,
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $navRows[] = [
                'isin'     => $isinGrowth,
                'nav_date' => $navDateParsed,
                'nav'      => (float)$nav,
            ];
        }

        return [array_values($masterRows), $navRows];
    }

    private function parseCategory(string $raw): array
    {
        $raw = trim($raw);
        // "Equity Scheme - Large Cap Fund" → category=Equity, sub comes from full string
        $map = [
            'equity'   => 'Equity',
            'debt'     => 'Debt',
            'hybrid'   => 'Hybrid',
            'solution' => 'Solution',
            'index'    => 'Index',
            'etf'      => 'ETF',
            'fof'      => 'FoF',
        ];
        $category = 'Other';
        foreach ($map as $key => $label) {
            if (stripos($raw, $key) !== false) { $category = $label; break; }
        }
        // Derive Growth/IDCW from scheme name context — set at master level later;
        // file-level category block tells us fund type, not plan type
        return [$category, null];
    }

    private function parseDate(string $d): ?string
    {
        try {
            // AMFI uses DD-Mon-YYYY e.g. "16-Apr-2026"
            return Carbon::createFromFormat('d-M-Y', $d)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // DB writes
    // -------------------------------------------------------------------------

    private function upsertMaster(array $rows): void
    {
        $this->info('Upserting mutual_funds...');
        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk($rows, self::MASTER_CHUNK) as $chunk) {
            DB::table('mutual_funds')->upsert(
                $chunk,
                ['isin'],
                ['scheme_code', 'isin_reinvest', 'scheme_name', 'amc_name', 'category', 'type', 'is_active', 'updated_at']
            );
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
    }

    private function upsertNav(array $rows): void
    {
        $this->info('Upserting mutual_fund_prices...');

        // Only insert for ISINs that exist in master (FK constraint)
        $knownIsins = DB::table('mutual_funds')->pluck('isin')->flip();
        $rows = array_filter($rows, fn($r) => isset($knownIsins[$r['isin']]));

        $bar = $this->output->createProgressBar(count($rows));
        $bar->start();

        foreach (array_chunk(array_values($rows), self::NAV_CHUNK) as $chunk) {
            DB::table('mutual_fund_prices')->upsert(
                $chunk,
                ['isin', 'nav_date'],
                ['nav']
            );
            $bar->advance(count($chunk));
        }

        $bar->finish();
        $this->newLine();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function inNavWindow(): bool
    {
        $hour = (int) now('Asia/Kolkata')->format('G');
        return $hour >= self::NAV_WINDOW_START && $hour < self::NAV_WINDOW_END;
    }

    // -------------------------------------------------------------------------
    // Returns computation for today's NAV date
    // -------------------------------------------------------------------------

    private function computeReturnsForDate(string $navDate): void
    {
        $this->info('Computing returns for ' . $navDate . '...');

        $periods = [
            'chg_1d' => Carbon::parse($navDate)->subDay(),
            'chg_3d' => Carbon::parse($navDate)->subDays(3),
            'chg_7d' => Carbon::parse($navDate)->subDays(7),
            'chg_1m' => Carbon::parse($navDate)->subMonth(),
            'chg_3m' => Carbon::parse($navDate)->subMonths(3),
            'chg_6m' => Carbon::parse($navDate)->subMonths(6),
            'chg_9m' => Carbon::parse($navDate)->subMonths(9),
            'chg_1y' => Carbon::parse($navDate)->subYear(),
            'chg_3y' => Carbon::parse($navDate)->subYears(3),
        ];

        // Oldest date we need history from (3y - 10 days buffer)
        $oldest = Carbon::parse($navDate)->subYears(3)->subDays(10)->format('Y-m-d');

        // Today's NAVs
        $todayNavs = DB::table('mutual_fund_prices')
            ->where('nav_date', $navDate)
            ->select('isin', 'nav')
            ->get();

        if ($todayNavs->isEmpty()) return;

        $bar = $this->output->createProgressBar($todayNavs->count());
        $bar->start();

        // Process in chunks to limit memory usage (~500 schemes × ~650 rows = 325k rows)
        foreach ($todayNavs->chunk(500) as $chunk) {
            $isins = $chunk->pluck('isin')->all();

            // Fetch all historical NAVs for these ISINs in one query
            $hist = DB::table('mutual_fund_prices')
                ->whereIn('isin', $isins)
                ->where('nav_date', '>=', $oldest)
                ->where('nav_date', '<', $navDate)
                ->orderBy('nav_date')
                ->select('isin', 'nav_date', 'nav')
                ->get()
                ->groupBy('isin')
                ->map(fn($rows) => $rows->sortBy('nav_date')->values());

            foreach ($chunk as $row) {
                $currentNav = (float) $row->nav;
                $schemeHist = $hist->get($row->isin, collect());

                $updates = [];
                foreach ($periods as $col => $targetCarbon) {
                    $targetTs  = $targetCarbon->timestamp;
                    $windowSec = 10 * 86400;

                    $best = null;
                    $bestDiff = PHP_INT_MAX;

                    foreach ($schemeHist as $h) {
                        $diff = abs(strtotime($h->nav_date) - $targetTs);
                        if ($diff <= $windowSec && $diff < $bestDiff) {
                            $bestDiff = $diff;
                            $best     = $h;
                        }
                    }

                    $refCol = str_replace('chg_', 'val_', $col);
                    if ($best && (float)$best->nav > 0) {
                        $refNav         = (float)$best->nav;
                        $updates[$col]    = round((($currentNav - $refNav) / $refNav) * 100, 4);
                        $updates[$refCol] = $refNav;
                    } else {
                        $updates[$col]    = null;
                        $updates[$refCol] = null;
                    }
                }

                DB::table('mutual_fund_prices')
                    ->where('isin', $row->isin)
                    ->where('nav_date', $navDate)
                    ->update($updates);

                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
    }
}
