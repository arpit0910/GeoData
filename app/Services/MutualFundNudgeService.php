<?php

namespace App\Services;

use App\Models\MutualFundNudge;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MutualFundNudgeService
{
    public function generateForDate(string $navDate): int
    {
        $date = Carbon::createFromFormat('Y-m-d', $navDate, 'Asia/Kolkata')->startOfDay();
        $rows = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as f', 'f.id', '=', 'p.mf_id')
            ->whereDate('p.nav_date', $navDate)
            ->where('f.is_active', true)
            ->where('p.nav', '>', 0)
            ->select('p.*', 'f.id as mutual_fund_id', 'f.scheme_name')
            ->orderBy('p.id')
            ->get();

        if ($rows->isEmpty()) {
            return 0;
        }

        $history = DB::table('mutual_fund_prices')
            ->whereIn('isin', $rows->pluck('isin')->unique()->all())
            ->where('nav_date', '<', $navDate)
            ->where('nav_date', '>=', $date->copy()->subYear()->toDateString())
            ->groupBy('isin')
            ->select('isin')
            ->where('nav', '>', 0)
            ->selectRaw('MAX(CASE WHEN nav_date >= ? THEN nav END) as month_high', [$date->copy()->subMonth()->toDateString()])
            ->selectRaw('MIN(CASE WHEN nav_date >= ? THEN nav END) as month_low', [$date->copy()->subMonth()->toDateString()])
            ->selectRaw('MAX(nav) as year_high')
            ->selectRaw('MIN(nav) as year_low')
            ->get()
            ->keyBy('isin');

        $created = 0;
        foreach ($rows as $row) {
            $nav = (float) $row->nav;
            $changePercent = is_numeric($row->chg_1d) ? (float) $row->chg_1d : null;
            if ($changePercent !== null && abs($changePercent) >= (float) config('stock_nudges.mf_daily_move_percent')) {
                $direction = $changePercent > 0 ? 'gain' : 'drop';
                $previousNav = is_numeric($row->val_1d) ? (float) $row->val_1d : $nav / (1 + ($changePercent / 100));
                $created += $this->store(
                    $row,
                    'daily_'.$direction,
                    'significant',
                    sprintf('%s NAV is %s %.2f%%', $row->scheme_name, $changePercent > 0 ? 'up' : 'down', abs($changePercent)),
                    sprintf('%s NAV moved from ₹%s to ₹%s (%.2f%%).', $row->scheme_name, number_format($previousNav, 4), number_format($nav, 4), $changePercent),
                    ['previous_nav' => round($previousNav, 4), 'change_percent' => round($changePercent, 2)],
                    $date
                );
            }

            foreach (['high', 'low'] as $side) {
                $record = $this->recordRule($history->get($row->isin), $nav, $side);
                if ($record === null) {
                    continue;
                }
                [$rule, $previousLevel, $label] = $record;
                $difference = $nav - $previousLevel;
                $differencePercent = ($difference / $previousLevel) * 100;
                $movement = $side === 'high' ? 'above' : 'below';
                $created += $this->store(
                    $row,
                    $rule,
                    str_starts_with($rule, 'year_') ? 'significant' : 'notable',
                    sprintf('%s reached %s', $row->scheme_name, $label),
                    sprintf('%s NAV set %s at ₹%s, ₹%s (%.2f%%) %s its previous %s of ₹%s.', $row->scheme_name, $label, number_format($nav, 4), number_format(abs($difference), 4), abs($differencePercent), $movement, str_replace('_', '-', $rule), number_format($previousLevel, 4)),
                    ['previous_level' => $previousLevel, 'difference' => round($difference, 4), 'difference_percent' => round($differencePercent, 2)],
                    $date
                );
            }
        }

        return $created;
    }

    private function recordRule(?object $history, float $nav, string $side): ?array
    {
        if (! $history) {
            return null;
        }

        $comparison = $side === 'high'
            ? fn (float $level) => $nav > $level
            : fn (float $level) => $nav < $level;
        foreach ([['year_'.$side, 'a new 52-week '.$side], ['month_'.$side, 'a new one-month '.$side]] as [$rule, $label]) {
            $level = is_numeric($history->{$rule}) ? (float) $history->{$rule} : 0;
            if ($level > 0 && $comparison($level)) {
                return [$rule, $level, $label];
            }
        }

        return null;
    }

    private function store(object $fund, string $rule, string $severity, string $title, string $message, array $metrics, Carbon $date): int
    {
        return MutualFundNudge::query()->insertOrIgnore([
            'mutual_fund_id' => $fund->mutual_fund_id,
            'isin' => $fund->isin,
            'rule' => $rule,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'observed_nav' => $fund->nav,
            'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
            'nav_date' => $date->toDateString(),
            'observed_at' => now('Asia/Kolkata')->utc(),
            'fingerprint' => hash('sha256', implode('|', [$fund->isin, $rule, $date->toDateString()])),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
