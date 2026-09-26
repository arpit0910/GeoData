<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Services\CompanyFundamentalsSyncService;
use App\Services\UpstoxTokenManager;
use Illuminate\Console\Command;

class SyncCompanyFundamentalsCommand extends Command
{
    protected $signature = 'market:sync-company-fundamentals
        {--isin=* : Synchronize only the specified ISINs}
        {--dataset=* : Synchronize only selected datasets}
        {--limit=25 : Maximum companies selected by the scheduled batch}
        {--all : Synchronize every eligible company}
        {--stale-days= : Synchronize only companies never synced or not synced within this many days}
        {--delay=250 : Delay in milliseconds between provider requests}';

    protected $description = 'Synchronize company profiles, statements, ratios, holdings, actions, and competitors';

    public function handle(CompanyFundamentalsSyncService $syncService, UpstoxTokenManager $tokens): int
    {
        try {
            $tokens->accessToken();
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $datasets = collect($this->option('dataset'))
            ->map(fn ($dataset) => strtolower(trim((string) $dataset)))
            ->filter()
            ->unique()
            ->values();
        $invalidDatasets = $datasets->diff(CompanyFundamentalsSyncService::DATASETS);
        if ($invalidDatasets->isNotEmpty()) {
            $this->error('Unsupported datasets: '.$invalidDatasets->implode(', '));

            return self::INVALID;
        }

        $delayMs = max(0, min((int) $this->option('delay'), 5000));
        $isins = collect($this->option('isin'))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))
            ->filter()
            ->unique()
            ->values();

        $query = Equity::query()
            ->where('is_active', true)
            ->whereIn('series', ['EQ', 'BE', 'SM', 'BZ'])
            ->whereNotNull('isin')
            ->where('isin', '<>', '');

        if ($isins->isNotEmpty()) {
            $query->whereIn('isin', $isins->all());
        } else {
            if ($this->option('stale-days') !== null) {
                $staleDays = max(0, min((int) $this->option('stale-days'), 3650));
                $cutoff = now()->subDays($staleDays);
                $query->where(function ($query) use ($cutoff) {
                    $query
                        ->whereNotExists(function ($subquery) {
                            $subquery->selectRaw('1')
                                ->from('company_fundamentals')
                                ->whereColumn('company_fundamentals.isin', 'equities.isin');
                        })
                        ->orWhereRaw(
                            '(SELECT MAX(company_fundamentals.synced_at) FROM company_fundamentals WHERE company_fundamentals.isin = equities.isin) <= ?',
                            [$cutoff]
                        );
                });
            }

            $query
                ->orderByRaw('(SELECT MAX(company_fundamentals.synced_at) FROM company_fundamentals WHERE company_fundamentals.isin = equities.isin) IS NULL DESC')
                ->orderByRaw('(SELECT MAX(company_fundamentals.synced_at) FROM company_fundamentals WHERE company_fundamentals.isin = equities.isin) ASC');

            if (!$this->option('all')) {
                $query->limit(max(1, min((int) $this->option('limit'), 1000)));
            }
        }

        $equities = $query->get();
        if ($equities->isEmpty()) {
            $this->warn('No eligible equities were found for fundamentals synchronization.');

            return self::SUCCESS;
        }

        $totals = ['companies' => 0, 'requested' => 0, 'saved' => 0, 'failed' => 0];
        $authenticationFailed = false;

        foreach ($equities as $equity) {
            $stats = $syncService->sync($equity, $datasets->all(), $delayMs);
            $totals['companies']++;
            $totals['requested'] += $stats['requested'];
            $totals['saved'] += $stats['saved'];
            $totals['failed'] += $stats['failed'];

            $this->line(
                "{$equity->isin}: saved {$stats['saved']} of {$stats['requested']} datasets"
                .($stats['failed'] > 0 ? "; failed {$stats['failed']}" : '')
            );

            foreach (array_unique($stats['errors']) as $error) {
                $this->warn("{$equity->isin}: {$error}");
            }

            $authenticationFailed = collect($stats['errors'])->contains(
                fn (string $error) => preg_match('/HTTP (401|403)\b/', $error) === 1
            );
            if ($authenticationFailed) {
                $this->error('Company fundamentals synchronization stopped because the Upstox access token was rejected. Configure a fresh UPSTOX_ACCESS_TOKEN and clear the Laravel configuration cache.');
                try {
                    $renewal = $tokens->requestRenewal();
                    $this->warn($renewal['requested']
                        ? 'A replacement token was requested. Approve the request in Upstox.'
                        : 'A replacement token request is already awaiting approval in Upstox.');
                } catch (\Throwable $exception) {
                    $this->warn('Automatic token renewal could not be initiated: '.$exception->getMessage());
                }
                break;
            }
        }

        $this->info(
            "Company fundamentals synchronized. Companies: {$totals['companies']}; "
            ."requested: {$totals['requested']}; saved: {$totals['saved']}; failed: {$totals['failed']}."
        );

        return $totals['saved'] > 0 && $totals['failed'] === 0 && ! $authenticationFailed
            ? self::SUCCESS
            : self::FAILURE;
    }
}
