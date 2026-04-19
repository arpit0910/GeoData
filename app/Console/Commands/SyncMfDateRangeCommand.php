<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncMfDateRangeCommand extends Command
{
    protected $signature = 'sync:mf-range
                            {--from= : Start date YYYY-MM-DD (default: 2023-04-01)}
                            {--to=   : End date YYYY-MM-DD (default: today)}
                            {--force : Re-sync even if date already has records}';

    protected $description = 'Fetch AMFI historical NAVs day-by-day and upsert mutual_funds + mutual_fund_prices with returns';

    // AMFI historical endpoint — returns the same semicolon-delimited format as NAVAll.txt
    private const AMFI_HIST_URL = 'https://portal.amfiindia.com/DownloadNAVHistoryReport_Po.aspx';
    private const MASTER_CHUNK  = 500;
    private const NAV_CHUNK     = 500;

    private const PERIODS = [
        'chg_1d' => ['subDay',    1],
        'chg_3d' => ['subDays',   3],
        'chg_7d' => ['subDays',   7],
        'chg_1m' => ['subMonth',  1],
        'chg_3m' => ['subMonths', 3],
        'chg_6m' => ['subMonths', 6],
        'chg_9m' => ['subMonths', 9],
        'chg_1y' => ['subYear',   1],
        'chg_3y' => ['subYears',  3],
    ];

    public function handle(): int
    {
        ini_set('memory_limit', '512M');

        $from = Carbon::parse($this->option('from') ?? '2023-04-01')->startOfDay();
        $to   = Carbon::parse($this->option('to')   ?? now())->startOfDay();

        $this->info(sprintf('Syncing MF NAVs from %s to %s...', $from->format('Y-m-d'), $to->format('Y-m-d')));

        $current   = $from->clone();
        $totalDays = (int) $from->diffInDays($to);
        $bar       = $this->output->createProgressBar($totalDays + 1);
        $bar->start();

        while ($current <= $to) {
            $date = $current->format('Y-m-d');

            // Skip weekends — AMFI publishes no NAVs on Sat/Sun
            if ($current->isWeekend()) {
                $current->addDay();
                $bar->advance();
                continue;
            }

            if (!$this->option('force')) {
                $exists = DB::table('mutual_fund_prices')->where('nav_date', $date)->exists();
                if ($exists) {
                    $this->line("\n  <fg=yellow>Skip</> {$date} — already in DB");
                    $current->addDay();
                    $bar->advance();
                    continue;
                }
            }

            $this->line("\n  Processing {$date}...");

            try {
                $body = $this->fetchAmfiForDate($current);
                if (!$body) {
                    $this->warn("  No data returned for {$date}");
                    $current->addDay();
                    $bar->advance();
                    continue;
                }

                [$masterRows, $navRows] = $this->parse($body, $date);

                if (empty($navRows)) {
                    $this->warn("  Parsed 0 NAV rows for {$date} (holiday/no data)");
                    $current->addDay();
                    $bar->advance();
                    continue;
                }

                $this->info(sprintf('  Parsed %d schemes, %d NAV rows', count($masterRows), count($navRows)));

                $this->upsertMaster($masterRows);
                $this->upsertNav($navRows, $date);
                $this->computeReturns($date);

            } catch (\Exception $e) {
                $this->error("  Failed {$date}: " . $e->getMessage());
            }

            $current->addDay();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('sync:mf-range complete.');

        return Command::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function fetchAmfiForDate(Carbon $date): ?string
    {
        $fmt = $date->format('d-M-Y'); // e.g. "01-Apr-2023"

        try {
            $response = Http::timeout(60)
                ->withoutVerifying()
                ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                ->get(self::AMFI_HIST_URL, ['frmdt' => $fmt, 'todt' => $fmt]);

            if (!$response->successful() || strlen($response->body()) < 200) {
                return null;
            }
            return $response->body();
        } catch (\Exception $e) {
            $this->warn('  AMFI fetch error: ' . $e->getMessage());
            return null;
        }
    }

    private function parse(string $body, string $date): array
    {
        $lines      = explode("\n", str_replace("\r", '', $body));
        $masterRows = [];
        $navRows    = [];

        $currentAmc      = null;
        $currentCategory = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, 'Scheme Code;')) continue;

            $fields = explode(';', $line);

            if (count($fields) === 1) {
                if (preg_match('/(?:Open|Close|Interval)[^(]*\(\s*(.+?)\s*\)/i', $line, $m)) {
                    $currentCategory = $this->parseCategory($m[1]);
                } else {
                    $currentAmc = $line;
                }
                continue;
            }

            // Historical endpoint format (8 fields):
            // Scheme Code ; Scheme Name ; ISIN Growth ; ISIN Reinvest ; NAV ; Repurchase ; Sale ; Date
            if (count($fields) < 8) continue;

            $fields     = array_map('trim', $fields);
            $schemeCode  = $fields[0];
            $schemeName  = $fields[1];
            $isinGrowth  = $fields[2];
            $isinReinvest= $fields[3];
            $nav         = $fields[4];
            $navDate     = $fields[7];

            if (!is_numeric($schemeCode) || strlen($isinGrowth) !== 12) continue;
            if (!is_numeric($nav) || (float)$nav <= 0) continue;

            $parsedDate = $this->parseDate($navDate);
            if (!$parsedDate) continue;

            $masterRows[$isinGrowth] = [
                'isin'          => $isinGrowth,
                'scheme_code'   => $schemeCode,
                'isin_reinvest' => strlen($isinReinvest) === 12 ? $isinReinvest : null,
                'scheme_name'   => substr($schemeName, 0, 300),
                'amc_name'      => $currentAmc,
                'category'      => $currentCategory,
                'sub_category'  => null,
                'type'          => null,
                'is_active'     => 1,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $navRows[] = [
                'isin'     => $isinGrowth,
                'nav_date' => $parsedDate,
                'nav'      => (float)$nav,
            ];
        }

        return [array_values($masterRows), $navRows];
    }

    private function parseCategory(string $raw): string
    {
        $map = ['equity' => 'Equity', 'debt' => 'Debt', 'hybrid' => 'Hybrid',
                'solution' => 'Solution', 'index' => 'Index', 'etf' => 'ETF', 'fof' => 'FoF'];
        foreach ($map as $key => $label) {
            if (stripos($raw, $key) !== false) return $label;
        }
        return 'Other';
    }

    private function parseDate(string $d): ?string
    {
        try {
            return Carbon::createFromFormat('d-M-Y', $d)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function upsertMaster(array $rows): void
    {
        foreach (array_chunk($rows, self::MASTER_CHUNK) as $chunk) {
            DB::table('mutual_funds')->upsert(
                $chunk,
                ['isin'],
                ['scheme_code', 'isin_reinvest', 'scheme_name', 'amc_name', 'category', 'is_active', 'updated_at']
            );
        }
    }

    private function upsertNav(array $rows, string $date): void
    {
        $knownIsins = DB::table('mutual_funds')->pluck('isin')->flip();
        $rows = array_values(array_filter($rows, fn($r) => isset($knownIsins[$r['isin']])));

        foreach (array_chunk($rows, self::NAV_CHUNK) as $chunk) {
            DB::table('mutual_fund_prices')->upsert(
                $chunk,
                ['isin', 'nav_date'],
                ['nav']
            );
        }
    }

    private function computeReturns(string $navDate): void
    {
        $periods = [];
        foreach (self::PERIODS as $col => [$method, $val]) {
            $c = Carbon::parse($navDate);
            match ($method) {
                'subDay'    => $c->subDay(),
                'subDays'   => $c->subDays($val),
                'subMonth'  => $c->subMonth(),
                'subMonths' => $c->subMonths($val),
                'subYear'   => $c->subYear(),
                'subYears'  => $c->subYears($val),
            };
            $periods[$col] = $c;
        }

        $oldest = Carbon::parse($navDate)->subYears(3)->subDays(10)->format('Y-m-d');

        $todayNavs = DB::table('mutual_fund_prices')
            ->where('nav_date', $navDate)
            ->select('isin', 'nav')
            ->get();

        if ($todayNavs->isEmpty()) return;

        foreach ($todayNavs->chunk(500) as $chunk) {
            $isins = $chunk->pluck('isin')->all();

            $hist = DB::table('mutual_fund_prices')
                ->whereIn('isin', $isins)
                ->where('nav_date', '>=', $oldest)
                ->where('nav_date', '<', $navDate)
                ->orderBy('nav_date')
                ->select('isin', 'nav_date', 'nav')
                ->get()
                ->groupBy('isin')
                ->map(fn($rows) => $rows->sortBy('nav_date')->values());

            $updates = [];
            foreach ($chunk as $row) {
                $currentNav = (float)$row->nav;
                $schemeHist = $hist->get($row->isin, collect());

                $rowUpdates = [];
                foreach ($periods as $col => $targetCarbon) {
                    $targetTs  = $targetCarbon->timestamp;
                    $windowSec = 10 * 86400;
                    $best = null;
                    $bestDiff = PHP_INT_MAX;
                    foreach ($schemeHist as $h) {
                        $diff = abs(strtotime($h->nav_date) - $targetTs);
                        if ($diff <= $windowSec && $diff < $bestDiff) {
                            $bestDiff = $diff;
                            $best = $h;
                        }
                    }

                    $valCol = str_replace('chg_', 'val_', $col);
                    if ($best && (float)$best->nav > 0) {
                        $refNav = (float)$best->nav;
                        $rowUpdates[$col]    = round((($currentNav - $refNav) / $refNav) * 100, 4);
                        $rowUpdates[$valCol] = $refNav;
                    } else {
                        $rowUpdates[$col]    = null;
                        $rowUpdates[$valCol] = null;
                    }
                }

                DB::table('mutual_fund_prices')
                    ->where('isin', $row->isin)
                    ->where('nav_date', $navDate)
                    ->update($rowUpdates);
            }
        }
    }
}
