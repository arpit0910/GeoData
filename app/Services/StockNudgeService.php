<?php

namespace App\Services;

use App\Models\StockNudge;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockNudgeService
{
    public function generateMarket(?int $limit = null): int
    {
        $now = now('Asia/Kolkata');
        $freshAfter = $now->copy()->subMinutes(config('stock_nudges.max_quote_age_minutes'))->utc()->format('Y-m-d H:i:s');
        $query = DB::table('equity_quotes as q')
            ->join('equities as e', 'e.isin', '=', 'q.isin')
            ->where(function ($query) use ($freshAfter) {
                $query->where('q.nse_fetched_at', '>=', $freshAfter)
                    ->orWhere('q.bse_fetched_at', '>=', $freshAfter);
            })
            ->orderBy('q.id')
            ->select('q.*', 'e.id as equity_id', 'e.company_name', 'e.sector');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $created = 0;
        $benchmarks = $this->marketBenchmarks($now);
        $query->get()->chunk(500)->each(function (Collection $quotes) use ($now, $benchmarks, &$created) {
            $history = $this->history($quotes->pluck('equity_id')->all(), $now, false);

            foreach ($quotes as $quote) {
                $exchange = $this->preferredMarketExchange($quote, $now);
                if ($exchange === null) {
                    continue;
                }

                $price = (float) $quote->{$exchange.'_price'};
                $payload = json_decode((string) $quote->{$exchange.'_payload'}, true) ?: [];
                $changePercent = is_numeric($payload['dp'] ?? null) ? (float) $payload['dp'] : null;
                $expiresAt = $now->copy()->addMinutes(config('stock_nudges.market_expiry_minutes'));
                $created += $this->priceRules(
                    $quote, strtoupper($exchange), $price, $changePercent,
                    $history->get($quote->equity_id), 'market', $now, $now, $expiresAt,
                    null, null,
                    is_numeric($payload['previous_close'] ?? null) ? (float) $payload['previous_close'] : null
                );
                $created += $this->comparisonRules(
                    $quote, strtoupper($exchange), $price, $changePercent,
                    $this->stockBenchmarkCodes($quote), $benchmarks, $now, $expiresAt
                );
            }
        });

        return $created;
    }

    public function generateIndexMarket(?int $limit = null): int
    {
        $now = now('Asia/Kolkata');
        $freshAfter = $now->copy()->subMinutes(config('stock_nudges.max_quote_age_minutes'))->utc();
        $query = DB::table('index_quotes as q')
            ->join('indices as i', 'i.index_code', '=', 'q.index_code')
            ->where('q.fetched_at', '>=', $freshAfter)
            ->orderBy('q.id')
            ->select('q.*', 'i.index_name', 'i.exchange');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $quotes = $query->get();
        $benchmarks = $this->marketBenchmarks($now);
        $history = $this->indexHistory($quotes->pluck('index_code')->all(), $now, false);
        $created = 0;

        foreach ($quotes as $quote) {
            $created += $this->priceRules(
                $quote,
                strtoupper((string) $quote->exchange),
                (float) $quote->price,
                is_numeric($quote->change_percent) ? (float) $quote->change_percent : null,
                $history->get($quote->index_code),
                'market',
                $now,
                $now,
                $now->copy()->addMinutes(config('stock_nudges.market_expiry_minutes')),
                null,
                null,
                is_numeric($quote->previous_close) ? (float) $quote->previous_close : null
            );
            if ($quote->index_code !== config('stock_nudges.market_benchmark')) {
                $created += $this->comparisonRules(
                    $quote,
                    strtoupper((string) $quote->exchange),
                    (float) $quote->price,
                    is_numeric($quote->change_percent) ? (float) $quote->change_percent : null,
                    [(string) config('stock_nudges.market_benchmark')],
                    $benchmarks,
                    $now,
                    $now->copy()->addMinutes(config('stock_nudges.market_expiry_minutes'))
                );
            }
        }

        return $created;
    }

    public function generateEod(Carbon $date, ?int $limit = null): int
    {
        $query = DB::table('equity_prices as p')
            ->join('equities as e', 'e.id', '=', 'p.equity_id')
            ->whereDate('p.traded_date', $date->toDateString())
            ->orderBy('p.id')
            ->select('p.*', 'e.company_name');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $history = $this->history($rows->pluck('equity_id')->all(), $date, true);
        $created = 0;

        foreach ($rows as $row) {
            $exchange = $this->preferredEodExchange($row);
            if ($exchange === null) {
                continue;
            }

            $close = $row->{$exchange.'_close'};

            $previousClose = $row->{$exchange.'_prev_close'};
            $changePercent = is_numeric($previousClose) && (float) $previousClose > 0
                ? (((float) $close - (float) $previousClose) / (float) $previousClose) * 100
                : null;
            $created += $this->priceRules(
                $row,
                strtoupper($exchange),
                (float) $close,
                $changePercent,
                $history->get($row->equity_id),
                'eod',
                $date,
                $date->copy()->setTime(18, 15),
                null,
                is_numeric($row->{$exchange.'_high'}) ? (float) $row->{$exchange.'_high'} : null,
                is_numeric($row->{$exchange.'_low'}) ? (float) $row->{$exchange.'_low'} : null,
                is_numeric($previousClose) ? (float) $previousClose : null
            );

            $averageVolume = $this->metric($history->get($row->equity_id), $exchange.'_avg_volume_20');
            $volume = $row->{$exchange.'_volume'};
            $multiple = is_numeric($volume) && $averageVolume > 0 ? (float) $volume / $averageVolume : 0;
            if ($multiple >= config('stock_nudges.volume_spike_multiple')) {
                $aboveAveragePercent = ($multiple - 1) * 100;
                $created += $this->store($row, strtoupper($exchange), 'eod', 'volume_spike', 'notable',
                    sprintf('%s volume was %.2fx normal', $this->name($row), $multiple),
                        sprintf('%s traded %s shares on %s versus its recent average of %s shares—%.2f%% above normal.', $this->name($row), number_format((int) $volume), strtoupper($exchange), number_format((int) round($averageVolume)), $aboveAveragePercent),
                    (float) $close, ['volume' => (int) $volume, 'average_volume_20' => round($averageVolume), 'multiple' => round($multiple, 2), 'above_average_percent' => round($aboveAveragePercent, 2)],
                    $date, $date->copy()->setTime(18, 15), null);
            }
        }

        return $created;
    }

    public function generateIndexEod(Carbon $date, ?int $limit = null): int
    {
        $query = DB::table('indices_prices as p')
            ->join('indices as i', 'i.index_code', '=', 'p.index_code')
            ->whereDate('p.traded_date', $date->toDateString())
            ->whereNotNull('p.close')
            ->where('p.close', '>', 0)
            ->orderBy('p.id')
            ->select('p.*', 'i.index_name', 'i.exchange');

        if ($limit !== null) {
            $query->limit($limit);
        }

        $rows = $query->get();
        $history = $this->indexHistory($rows->pluck('index_code')->all(), $date, true);
        $created = 0;

        foreach ($rows as $row) {
            $previousClose = $row->prev_close;
            $changePercent = is_numeric($previousClose) && (float) $previousClose > 0
                ? (((float) $row->close - (float) $previousClose) / (float) $previousClose) * 100
                : null;
            $created += $this->priceRules(
                $row,
                strtoupper((string) $row->exchange),
                (float) $row->close,
                $changePercent,
                $history->get($row->index_code),
                'eod',
                $date,
                $date->copy()->setTime(18, 15),
                null,
                is_numeric($row->high) ? (float) $row->high : null,
                is_numeric($row->low) ? (float) $row->low : null,
                is_numeric($previousClose) ? (float) $previousClose : null
            );

            $averageVolume = $this->metric($history->get($row->index_code), 'avg_volume_20');
            $volume = $row->volume;
            $multiple = is_numeric($volume) && $averageVolume > 0 ? (float) $volume / $averageVolume : 0;
            if ($multiple >= config('stock_nudges.volume_spike_multiple')) {
                $aboveAveragePercent = ($multiple - 1) * 100;
                $created += $this->store($row, strtoupper((string) $row->exchange), 'eod', 'volume_spike', 'notable',
                    sprintf('%s volume was %.2fx normal', $this->name($row), $multiple),
                    sprintf('%s volume was %s on %s versus its recent average of %s—%.2f%% above normal.', $this->name($row), number_format((int) $volume), strtoupper((string) $row->exchange), number_format((int) round($averageVolume)), $aboveAveragePercent),
                    (float) $row->close, ['volume' => (int) $volume, 'average_volume_20' => round($averageVolume), 'multiple' => round($multiple, 2), 'above_average_percent' => round($aboveAveragePercent, 2)],
                    $date, $date->copy()->setTime(18, 15), null);
            }
        }

        return $created;
    }

    private function preferredMarketExchange(object $quote, Carbon $now): ?string
    {
        foreach (['nse', 'bse'] as $exchange) {
            $price = $quote->{$exchange.'_price'};
            $quotedAt = $quote->{$exchange.'_quoted_at'};
            if (is_numeric($price) && (float) $price > 0 && $quotedAt !== null
                && Carbon::parse($quotedAt, 'UTC')->gte($now->copy()->subMinutes(config('stock_nudges.max_quote_age_minutes')))) {
                return $exchange;
            }
        }

        return null;
    }

    private function preferredEodExchange(object $row): ?string
    {
        foreach (['nse', 'bse'] as $exchange) {
            if (is_numeric($row->{$exchange.'_close'}) && (float) $row->{$exchange.'_close'} > 0) {
                return $exchange;
            }
        }

        return null;
    }

    private function marketBenchmarks(Carbon $now): Collection
    {
        return DB::table('index_quotes as q')
            ->join('indices as i', 'i.index_code', '=', 'q.index_code')
            ->where('q.fetched_at', '>=', $now->copy()->subMinutes(config('stock_nudges.max_quote_age_minutes'))->utc())
            ->whereNotNull('q.change_percent')
            ->select('q.*', 'i.index_name', 'i.exchange', 'i.category')
            ->get()
            ->keyBy('index_code');
    }

    private function stockBenchmarkCodes(object $stock): array
    {
        $codes = [(string) config('stock_nudges.market_benchmark')];
        $sector = strtolower(trim((string) ($stock->sector ?? '')));

        foreach (config('stock_nudges.sector_indices', []) as $sectorName => $indexCode) {
            if ($sector !== '' && str_contains($sector, strtolower($sectorName))) {
                $codes[] = $indexCode;
                break;
            }
        }

        return array_values(array_unique(array_filter($codes)));
    }

    private function comparisonRules(object $instrument, string $exchange, float $price, ?float $changePercent, array $benchmarkCodes, Collection $benchmarks, Carbon $observedAt, ?Carbon $expiresAt): int
    {
        if ($changePercent === null) {
            return 0;
        }

        $created = 0;
        $threshold = (float) config('stock_nudges.comparison_move_percent');

        foreach ($benchmarkCodes as $benchmarkCode) {
            if (($instrument->index_code ?? null) === $benchmarkCode) {
                continue;
            }

            $benchmark = $benchmarks->get($benchmarkCode);
            if (!$benchmark || !is_numeric($benchmark->change_percent)) {
                continue;
            }

            $benchmarkChange = (float) $benchmark->change_percent;
            $relativeChange = $changePercent - $benchmarkChange;
            if (abs($relativeChange) < $threshold) {
                continue;
            }

            $direction = $relativeChange > 0 ? 'outperformance' : 'underperformance';
            $created += $this->store(
                $instrument,
                $exchange,
                'market',
                'index_'.$direction,
                'notable',
                sprintf('%s %s %s by %.2f%%', $this->name($instrument), $relativeChange > 0 ? 'outperformed' : 'underperformed', $benchmark->index_name, abs($relativeChange)),
                sprintf('%s moved %.2f%% versus %.2f%% for %s, a relative difference of %.2f%%.', $this->name($instrument), $changePercent, $benchmarkChange, $benchmark->index_name, $relativeChange),
                $price,
                [
                    'instrument_change_percent' => round($changePercent, 2),
                    'benchmark_index_code' => $benchmarkCode,
                    'benchmark_name' => $benchmark->index_name,
                    'benchmark_change_percent' => round($benchmarkChange, 2),
                    'relative_change_percent' => round($relativeChange, 2),
                ],
                $observedAt,
                $observedAt,
                $expiresAt
            );
        }

        return $created;
    }

    private function priceRules(object $stock, string $exchange, float $price, ?float $changePercent, ?object $history, string $type, Carbon $date, Carbon $observedAt, ?Carbon $expiresAt, ?float $sessionHigh = null, ?float $sessionLow = null, ?float $previousClose = null): int
    {
        $created = 0;
        $threshold = (float) config('stock_nudges.daily_move_percent');
        if ($changePercent !== null && abs($changePercent) >= $threshold) {
            $direction = $changePercent > 0 ? 'gain' : 'drop';
            $previousClose ??= $price / (1 + ($changePercent / 100));
            $absoluteChange = $price - $previousClose;
            $created += $this->store($stock, $exchange, $type, 'daily_'.$direction, 'significant',
                sprintf('%s is %s %.2f%%', $this->name($stock), $changePercent > 0 ? 'up' : 'down', abs($changePercent)),
                sprintf('%s is at ₹%s on %s, %s ₹%s (%.2f%%) from the previous close of ₹%s.', $this->name($stock), number_format($price, 2), $exchange, $changePercent > 0 ? 'up' : 'down', number_format(abs($absoluteChange), 2), abs($changePercent), number_format($previousClose, 2)),
                $price, ['previous_close' => round($previousClose, 4), 'absolute_change' => round($absoluteChange, 4), 'change_percent' => round($changePercent, 2)], $date, $observedAt, $expiresAt);
        }

        // A 52-week record also implies a monthly record, so emit only the stronger fact.
        foreach ([
            $this->recordRule($history, $exchange, $sessionHigh ?? $price, 'high'),
            $this->recordRule($history, $exchange, $sessionLow ?? $price, 'low'),
        ] as $record) {
            if ($record === null) {
                continue;
            }
            [$rule, $metric, $label, $side] = $record;
            $level = $this->metric($history, strtolower($exchange).'_'.$metric)
                ?: $this->metric($history, 'index_'.$metric);
            $rulePrice = $side === 'high' ? ($sessionHigh ?? $price) : ($sessionLow ?? $price);
            $difference = $rulePrice - $level;
            $differencePercent = ($difference / $level) * 100;
            $comparison = abs($difference) < 0.005
                ? sprintf('matching the previous %s of ₹%s', str_replace('_', '-', $rule), number_format($level, 2))
                : sprintf('%s ₹%s (%.2f%%) %s the previous %s of ₹%s', $difference > 0 ? 'up' : 'down', number_format(abs($difference), 2), abs($differencePercent), $side === 'high' ? 'above' : 'below', str_replace('_', '-', $rule), number_format($level, 2));
            $created += $this->store($stock, $exchange, $type, $rule, str_starts_with($rule, 'year_') ? 'significant' : 'notable',
                sprintf('%s hit %s at ₹%s', $this->name($stock), $label, number_format($rulePrice, 2)),
                sprintf('%s reached ₹%s on %s, %s.', $this->name($stock), number_format($rulePrice, 2), $exchange, $comparison),
                $rulePrice, ['previous_level' => $level, 'difference' => round($difference, 4), 'difference_percent' => round($differencePercent, 2), 'closing_price' => $price], $date, $observedAt, $expiresAt);
        }

        return $created;
    }

    private function recordRule(?object $history, string $exchange, float $price, string $side): ?array
    {
        $prefix = strtolower($exchange);
        $yearLevel = $this->metric($history, "{$prefix}_year_{$side}")
            ?: $this->metric($history, "index_year_{$side}");
        $monthLevel = $this->metric($history, "{$prefix}_month_{$side}")
            ?: $this->metric($history, "index_month_{$side}");
        $comparison = $side === 'high' ? fn (float $level) => $price >= $level : fn (float $level) => $price <= $level;

        if ($yearLevel > 0 && $comparison($yearLevel)) {
            return ["year_{$side}", "year_{$side}", "a new 52-week {$side}", $side];
        }
        if ($monthLevel > 0 && $comparison($monthLevel)) {
            return ["month_{$side}", "month_{$side}", "a new one-month {$side}", $side];
        }

        return null;
    }

    private function history(array $equityIds, Carbon $date, bool $withVolume): Collection
    {
        if ($equityIds === []) {
            return collect();
        }

        $monthStart = $date->copy()->subMonth()->toDateString();
        $yearStart = $date->copy()->subYear()->toDateString();
        $query = DB::table('equity_prices')
            ->whereIn('equity_id', array_unique($equityIds))
            ->where('traded_date', '<', $date->toDateString())
            ->where('traded_date', '>=', $yearStart)
            ->groupBy('equity_id')
            ->select('equity_id');

        foreach (['nse', 'bse'] as $exchange) {
            $query->selectRaw("MAX(CASE WHEN traded_date >= ? THEN {$exchange}_high END) as {$exchange}_month_high", [$monthStart])
                ->selectRaw("MIN(CASE WHEN traded_date >= ? AND {$exchange}_low > 0 THEN {$exchange}_low END) as {$exchange}_month_low", [$monthStart])
                ->selectRaw("MAX({$exchange}_high) as {$exchange}_year_high")
                ->selectRaw("MIN(CASE WHEN {$exchange}_low > 0 THEN {$exchange}_low END) as {$exchange}_year_low");
            if ($withVolume) {
                // shortcut: calendar-month average approximates 20 sessions; use window functions if exact session counts become material.
                $query->selectRaw("AVG(CASE WHEN traded_date >= ? THEN {$exchange}_volume END) as {$exchange}_avg_volume_20", [$monthStart]);
            }
        }

        return $query->get()->keyBy('equity_id');
    }

    private function indexHistory(array $indexCodes, Carbon $date, bool $withVolume): Collection
    {
        if ($indexCodes === []) {
            return collect();
        }

        $monthStart = $date->copy()->subMonth()->toDateString();
        $yearStart = $date->copy()->subYear()->toDateString();
        $query = DB::table('indices_prices')
            ->whereIn('index_code', array_unique($indexCodes))
            ->where('traded_date', '<', $date->toDateString())
            ->where('traded_date', '>=', $yearStart)
            ->groupBy('index_code')
            ->select('index_code')
            ->selectRaw('MAX(CASE WHEN traded_date >= ? THEN high END) as index_month_high', [$monthStart])
            ->selectRaw('MIN(CASE WHEN traded_date >= ? AND low > 0 THEN low END) as index_month_low', [$monthStart])
            ->selectRaw('MAX(high) as index_year_high')
            ->selectRaw('MIN(CASE WHEN low > 0 THEN low END) as index_year_low');

        if ($withVolume) {
            $query->selectRaw('AVG(CASE WHEN traded_date >= ? THEN volume END) as avg_volume_20', [$monthStart]);
        }

        return $query->get()->keyBy('index_code');
    }

    private function store(object $stock, string $exchange, string $type, string $rule, string $severity, string $title, string $message, float $price, array $metrics, Carbon $date, Carbon $observedAt, ?Carbon $expiresAt): int
    {
        $identifier = (string) ($stock->index_code ?? $stock->isin);
        $benchmarkIndexCode = $metrics['benchmark_index_code'] ?? null;
        $fingerprint = hash('sha256', implode('|', [$rule, $identifier, $exchange, $benchmarkIndexCode, $date->toDateString()]));

        if ($this->isSimilarRecentNudge($stock, $exchange, $rule, $price, $date, $benchmarkIndexCode)) {
            return 0;
        }

        return StockNudge::query()->insertOrIgnore([
            'equity_id' => $stock->equity_id ?? null,
            'isin' => $stock->isin ?? null,
            'index_code' => $stock->index_code ?? null,
            'exchange' => $exchange,
            'type' => $type,
            'rule' => $rule,
            'severity' => $severity,
            'title' => $title,
            'message' => $message,
            'observed_price' => $price,
            'metrics' => json_encode($metrics, JSON_THROW_ON_ERROR),
            'trading_date' => $date->toDateString(),
            'observed_at' => $observedAt->utc(),
            'expires_at' => $expiresAt?->utc(),
            'fingerprint' => $fingerprint,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isSimilarRecentNudge(object $stock, string $exchange, string $rule, float $price, Carbon $date, ?string $benchmarkIndexCode = null): bool
    {
        $matchingRule = StockNudge::query()
            ->when(
                isset($stock->index_code),
                fn ($query) => $query->where('index_code', $stock->index_code),
                fn ($query) => $query->where('isin', $stock->isin)
            )
            ->where('exchange', $exchange)
            ->where('rule', $rule)
            ->when($benchmarkIndexCode !== null, fn ($query) => $query->where('metrics->benchmark_index_code', $benchmarkIndexCode));

        if ((clone $matchingRule)->whereDate('trading_date', $date->toDateString())->exists()) {
            return true;
        }
        if (str_starts_with($rule, 'daily_')) {
            return false;
        }

        $last = $matchingRule
            ->whereDate('trading_date', '<', $date->toDateString())
            ->whereDate('trading_date', '>=', $date->copy()->subDays(config('stock_nudges.repeat_cooldown_days'))->toDateString())
            ->latest('trading_date')
            ->first();

        if (!$last) {
            return false;
        }
        if (!in_array($rule, ['month_high', 'month_low', 'year_high', 'year_low'], true)) {
            return true;
        }

        $extension = (float) config('stock_nudges.record_extension_percent') / 100;
        return str_ends_with($rule, '_high')
            ? $price < (float) $last->observed_price * (1 + $extension)
            : $price > (float) $last->observed_price * (1 - $extension);
    }

    private function metric(?object $history, string $field): float
    {
        return $history && is_numeric($history->{$field} ?? null) ? (float) $history->{$field} : 0;
    }

    private function name(object $stock): string
    {
        return trim((string) ($stock->company_name ?? $stock->index_name ?? ''))
            ?: (string) ($stock->isin ?? $stock->index_code);
    }
}
