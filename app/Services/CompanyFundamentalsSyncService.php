<?php

namespace App\Services;

use App\Models\CompanyFundamental;
use App\Models\Equity;
use Throwable;

class CompanyFundamentalsSyncService
{
    public const DATASETS = [
        'profile',
        'balance_sheet',
        'cash_flow',
        'income_statement',
        'share_holdings',
        'key_ratios',
        'corporate_actions',
        'competitors',
    ];

    public function __construct(
        private readonly UpstoxMarketDataService $marketData
    ) {
    }

    /** @return array{requested: int, saved: int, failed: int, errors: array<int, string>} */
    public function sync(Equity $equity, array $datasets = [], int $delayMs = 250): array
    {
        $definitions = collect($this->definitions())
            ->when($datasets !== [], fn ($items) => $items->whereIn('dataset', $datasets))
            ->values();

        $stats = ['requested' => 0, 'saved' => 0, 'failed' => 0, 'errors' => []];

        foreach ($definitions as $index => $definition) {
            $stats['requested']++;

            try {
                $payload = $this->marketData->fundamentals(
                    $definition['identifier'] === 'instrument_key'
                        ? (string) $equity->upstox_nse_instrument_key
                        : $equity->isin,
                    $definition['endpoint'],
                    $definition['query']
                );

                CompanyFundamental::updateOrCreate(
                    [
                        'isin' => $equity->isin,
                        'dataset' => $definition['dataset'],
                        'statement_type' => $definition['statement_type'],
                        'time_period' => $definition['time_period'],
                    ],
                    [
                        'equity_id' => $equity->id,
                        'payload' => $payload,
                        'synced_at' => now()->utc(),
                    ]
                );

                $stats['saved']++;
            } catch (Throwable $exception) {
                $stats['failed']++;
                $stats['errors'][] = $definition['dataset'].': '.$exception->getMessage();
                report($exception);
            }

            if ($delayMs > 0 && $index < $definitions->count() - 1) {
                usleep($delayMs * 1000);
            }
        }

        return $stats;
    }

    /** @return array<int, array{dataset: string, endpoint: string, statement_type: string, time_period: string, query: array<string, scalar>, identifier: string}> */
    private function definitions(): array
    {
        $definitions = [
            $this->definition('profile', 'profile'),
            $this->definition('share_holdings', 'share-holdings', 'not_applicable', 'quarterly'),
            $this->definition('key_ratios', 'key-ratios'),
            $this->definition('corporate_actions', 'corporate-actions'),
            $this->definition('competitors', 'competitors', identifier: 'instrument_key'),
        ];

        foreach (['consolidated', 'standalone'] as $statementType) {
            $definitions[] = $this->definition(
                'balance_sheet',
                'balance-sheet',
                $statementType,
                'yearly',
                ['type' => $statementType, 'fs' => 'true']
            );
            $definitions[] = $this->definition(
                'cash_flow',
                'cash-flow',
                $statementType,
                'yearly',
                ['type' => $statementType, 'fs' => 'true']
            );

            foreach (['yearly', 'quarterly'] as $timePeriod) {
                $definitions[] = $this->definition(
                    'income_statement',
                    'income-statement',
                    $statementType,
                    $timePeriod,
                    ['type' => $statementType, 'time_period' => $timePeriod, 'fs' => 'true']
                );
            }
        }

        return $definitions;
    }

    /** @param array<string, scalar> $query */
    private function definition(
        string $dataset,
        string $endpoint,
        string $statementType = 'not_applicable',
        string $timePeriod = 'not_applicable',
        array $query = [],
        string $identifier = 'isin'
    ): array {
        return [
            'dataset' => $dataset,
            'endpoint' => $endpoint,
            'statement_type' => $statementType,
            'time_period' => $timePeriod,
            'query' => $query,
            'identifier' => $identifier,
        ];
    }
}
