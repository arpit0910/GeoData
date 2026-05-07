<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MfService
{
    private const CUT_OFF_DATE = '2023-04-01';
    private const WINDOW_DAYS  = 10;

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

    private const UPDATE_COLS = [
        'chg_1d', 'val_1d', 'chg_3d', 'val_3d',
        'chg_7d', 'val_7d', 'chg_1m', 'val_1m',
        'chg_3m', 'val_3m', 'chg_6m', 'val_6m',
        'chg_9m', 'val_9m', 'chg_1y', 'val_1y',
        'chg_3y', 'val_3y',
    ];

    /**
     * Compute returns for every fund that has a record on $navDate.
     * Uses a preceding-date search: weekends/holidays fall back to the
     * nearest earlier trading day within WINDOW_DAYS.
     * 3y columns stay null until April 2026 (data starts April 2023).
     *
     * @return array{rows_written: int}
     */
    public function computeReturnsForDate(string $navDate): array
    {
        $periods = $this->buildPeriodTargets($navDate);
        $oldest  = Carbon::parse($navDate)->subYears(3)->subDays(self::WINDOW_DAYS)->format('Y-m-d');
        $written = 0;

        DB::table('mutual_fund_prices')
            ->where('nav_date', $navDate)
            ->orderBy('isin')
            ->select('isin', 'nav', 'nav_date', 'mf_id')
            ->chunk(500, function ($todayRows) use ($navDate, $oldest, $periods, &$written) {
                $isins = $todayRows->pluck('isin')->unique()->values()->all();

                // Fetch all history in one query, grouped by ISIN
                $history = DB::table('mutual_fund_prices')
                    ->whereIn('isin', $isins)
                    ->where('nav_date', '>=', $oldest)
                    ->where('nav_date', '<', $navDate)
                    ->orderBy('isin')
                    ->orderBy('nav_date')
                    ->select('isin', 'nav_date', 'nav')
                    ->get()
                    ->groupBy('isin')
                    ->map(fn($rows) => $rows->values());

                $upsertRows = [];
                foreach ($todayRows as $todayRow) {
                    $schemeHistory = $history->get($todayRow->isin, collect());
                    $upsertRows[]  = $this->buildReturnRow($todayRow, $schemeHistory, $periods);
                }

                if (!empty($upsertRows)) {
                    DB::table('mutual_fund_prices')->upsert($upsertRows, ['isin', 'nav_date'], self::UPDATE_COLS);
                    $written += count($upsertRows);
                }
            });

        return ['rows_written' => $written];
    }

    /**
     * Compute returns for a single ISIN across its entire saved history.
     * Skips rows that already have returns unless $force is true.
     *
     * @return int  Number of rows written
     */
    public function computeReturnsForIsin(string $isin, bool $force = false): int
    {
        $prices = DB::table('mutual_fund_prices')
            ->where('isin', $isin)
            ->orderBy('nav_date')
            ->select('isin', 'nav_date', 'nav', 'mf_id', 'chg_1d')
            ->get();

        if ($prices->isEmpty()) return 0;

        $allDates      = $prices->pluck('nav_date')->all();
        $allNavs       = $prices->pluck('nav')->all();
        $allTimestamps = array_map('strtotime', $allDates);

        $rows = [];
        foreach ($prices as $i => $row) {
            if (!$force && $row->chg_1d !== null) continue;

            $currentNav = (float) $row->nav;
            $updateRow  = [
                'isin'     => $isin,
                'nav_date' => $row->nav_date,
                'nav'      => $currentNav,
                'mf_id'    => $row->mf_id,
            ];

            foreach ($this->buildPeriodTargets($row->nav_date) as $label => $targetTs) {
                $idx = $this->closestPrecedingIdx($allTimestamps, $i, $targetTs);

                if ($idx !== null && (float) $allNavs[$idx] > 0) {
                    $refNav                     = (float) $allNavs[$idx];
                    $updateRow["chg_{$label}"]  = round((($currentNav - $refNav) / $refNav) * 100, 4);
                    $updateRow["val_{$label}"]  = $refNav;
                } else {
                    $updateRow["chg_{$label}"]  = null;
                    $updateRow["val_{$label}"]  = null;
                }
            }

            $rows[] = $updateRow;
        }

        if (empty($rows)) return 0;

        foreach (array_chunk($rows, 2000) as $chunk) {
            DB::table('mutual_fund_prices')->upsert($chunk, ['isin', 'nav_date'], self::UPDATE_COLS);
        }

        return count($rows);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Build period target timestamps for a given base date.
     * Keys are period labels (e.g. '1d', '3m', '3y').
     *
     * @return array<string, int>
     */
    private function buildPeriodTargets(string $baseDate): array
    {
        $targets = [];
        foreach (self::PERIODS as $chgCol => [$method, $val]) {
            $label = str_replace('chg_', '', $chgCol);
            $c     = Carbon::createFromFormat('Y-m-d', $baseDate);
            match ($method) {
                'subDay'    => $c->subDay(),
                'subDays'   => $c->subDays($val),
                'subMonth'  => $c->subMonth(),
                'subMonths' => $c->subMonths($val),
                'subYear'   => $c->subYear(),
                'subYears'  => $c->subYears($val),
            };
            $targets[$label] = $c->timestamp;
        }
        return $targets;
    }

    /**
     * @param  \Illuminate\Support\Collection $schemeHistory  Rows ordered by nav_date ASC
     * @param  array<string, int>             $periods        label → target timestamp
     */
    private function buildReturnRow(object $todayRow, $schemeHistory, array $periods): array
    {
        $currentNav = (float) $todayRow->nav;
        $row        = [
            'isin'     => $todayRow->isin,
            'nav_date' => $todayRow->nav_date,
            'nav'      => $currentNav,
            'mf_id'    => $todayRow->mf_id,
        ];

        foreach ($periods as $label => $targetTs) {
            $chgCol = "chg_{$label}";
            $valCol = "val_{$label}";
            $refNav = $this->findPrecedingNav($schemeHistory, $targetTs);

            if ($refNav !== null && $refNav > 0.0) {
                $row[$chgCol] = round((($currentNav - $refNav) / $refNav) * 100, 4);
                $row[$valCol] = $refNav;
            } else {
                $row[$chgCol] = null;
                $row[$valCol] = null;
            }
        }

        return $row;
    }

    /**
     * Find the NAV on the nearest trading day at or before $targetTs.
     * Searches only backwards — correctly handles weekends, holidays, and
     * the 3-year data gap (returns null when no data exists before April 2026).
     *
     * @param  \Illuminate\Support\Collection $history  Rows with nav_date and nav, ordered ASC
     */
    private function findPrecedingNav($history, int $targetTs): ?float
    {
        $windowSec = self::WINDOW_DAYS * 86400;
        $best      = null;
        $bestDiff  = PHP_INT_MAX;

        foreach ($history as $row) {
            $rowTs = strtotime($row->nav_date);

            if ($rowTs > $targetTs) break; // rows are sorted ASC; nothing ahead can match

            $diff = $targetTs - $rowTs; // always >= 0 (only preceding dates)
            if ($diff <= $windowSec && $diff < $bestDiff) {
                $bestDiff = $diff;
                $best     = (float) $row->nav;
            }
        }

        return $best;
    }

    /**
     * Binary-search for the closest preceding index in a sorted timestamp array.
     * Looks backwards only — never forward.
     *
     * @param  int[] $timestamps  Sorted ASC
     * @param  int   $currentIdx  Upper bound (exclusive) — the row we are computing for
     * @param  int   $targetTs    Unix timestamp to search for
     */
    private function closestPrecedingIdx(array $timestamps, int $currentIdx, int $targetTs): ?int
    {
        if ($currentIdx === 0) return null;

        $windowSec = self::WINDOW_DAYS * 86400;
        $lo        = 0;
        $hi        = $currentIdx - 1;

        // Find rightmost position where timestamp <= targetTs
        while ($lo < $hi) {
            $mid = ($lo + $hi + 1) >> 1;
            if ($timestamps[$mid] <= $targetTs) $lo = $mid;
            else $hi = $mid - 1;
        }

        if ($timestamps[$lo] > $targetTs) return null;

        $diff = $targetTs - $timestamps[$lo]; // always >= 0
        return $diff <= $windowSec ? $lo : null;
    }
}
