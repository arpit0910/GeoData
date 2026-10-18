<?php

namespace App\Services;

use App\Models\Ipo;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class UpstoxInsightsSyncService
{
    private const IPO_STATUSES = ['upcoming', 'open', 'closed', 'listed'];

    private const FII_DATA_TYPES = [
        'NSE_FO|INDEX_FUTURES',
        'NSE_FO|STOCK_FUTURES',
        'NSE_FO|INDEX_OPTIONS',
        'NSE_FO|STOCK_OPTIONS',
        'NSE_EQ|CASH',
    ];

    public function __construct(private readonly UpstoxMarketDataService $marketData)
    {
    }

    /** @return array{received: int, saved: int, detail_errors: int} */
    public function syncIpos(int $delayMs = 100): array
    {
        $stats = ['received' => 0, 'saved' => 0, 'detail_errors' => 0];

        foreach (self::IPO_STATUSES as $status) {
            $page = 1;
            do {
                $result = $this->marketData->ipos($status, $page);
                foreach ($result['data'] as $summary) {
                    if (!is_array($summary) || empty($summary['id']) || empty($summary['name'])) {
                        continue;
                    }

                    $stats['received']++;
                    $existing = Ipo::query()->where('slug', $summary['id'])->first();
                    $details = array_replace($existing?->details ?? [], $summary);
                    $shouldRefreshDetails = ($summary['status'] ?? null) !== 'listed'
                        || !$existing
                        || $existing->status !== 'listed'
                        || empty($existing->details);

                    if ($shouldRefreshDetails) {
                        try {
                            $details = array_replace(
                                $details,
                                $this->marketData->ipoDetails((string) $summary['id'])
                            );
                        } catch (Throwable $exception) {
                            if (preg_match('/HTTP (401|403)\b/', $exception->getMessage()) === 1) {
                                throw $exception;
                            }
                            $stats['detail_errors']++;
                        }
                    }

                    $this->saveIpo(array_replace($summary, $details));
                    $stats['saved']++;

                    if ($shouldRefreshDetails && $delayMs > 0) {
                        usleep($delayMs * 1000);
                    }
                }
                $page++;
            } while ($page <= $result['total_pages']);
        }

        return $stats;
    }

    /** @return array{received: int, saved: int} */
    public function syncInstitutionalActivity(): array
    {
        $rows = [];
        foreach (['1D', '1M'] as $interval) {
            foreach ([
                'fii' => self::FII_DATA_TYPES,
                'dii' => ['NSE_EQ|CASH'],
            ] as $investorType => $dataTypes) {
                $datasets = $this->marketData->institutionalActivity($investorType, $dataTypes, $interval);
                foreach ($datasets as $dataType => $records) {
                    if (!is_array($records)) {
                        continue;
                    }
                    foreach ($records as $record) {
                        if (!is_array($record) || !is_numeric($record['time_stamp'] ?? null)) {
                            continue;
                        }

                        $activityAt = Carbon::createFromTimestampMsUTC((int) $record['time_stamp'])
                            ->format('Y-m-d H:i:s');
                        $key = "{$investorType}|{$dataType}|{$interval}|{$activityAt}";
                        $rows[$key] = $this->activityRow($investorType, (string) $dataType, $interval, $activityAt, $record);
                    }
                }
            }
        }

        foreach (array_chunk(array_values($rows), 500) as $chunk) {
            DB::table('institutional_activities')->upsert(
                $chunk,
                ['investor_type', 'data_type', 'interval', 'activity_at'],
                [
                    'buy_amount', 'sell_amount', 'buy_contracts', 'sell_contracts',
                    'oi_contracts', 'oi_amount', 'total_long_contracts', 'total_short_contracts',
                    'total_call_long_contracts', 'total_put_long_contracts',
                    'total_call_short_contracts', 'total_put_short_contracts',
                    'synced_at', 'updated_at',
                ]
            );
        }

        return ['received' => count($rows), 'saved' => count($rows)];
    }

    /** @param array<string, mixed> $data */
    private function saveIpo(array $data): void
    {
        Ipo::updateOrCreate(
            ['slug' => trim((string) $data['id'])],
            [
                'symbol' => $this->string($data['symbol'] ?? null, 50),
                'name' => mb_substr(trim((string) $data['name']), 0, 255),
                'status' => $this->string($data['status'] ?? null, 20) ?: 'unknown',
                'isin' => $this->string($data['isin'] ?? null, 20),
                'issue_type' => $this->string($data['issue_type'] ?? null, 20),
                'issue_size' => $this->number($data['issue_size'] ?? null),
                'industry' => $this->string($data['industry'] ?? null, 255),
                'minimum_price' => $this->number($data['minimum_price'] ?? null),
                'maximum_price' => $this->number($data['maximum_price'] ?? null),
                'bidding_start_date' => $this->date($data['bidding_start_date'] ?? null),
                'bidding_end_date' => $this->date($data['bidding_end_date'] ?? null),
                'total_subscription' => $this->number($data['total_subscription'] ?? null),
                'details' => $data,
                'synced_at' => now()->utc(),
            ]
        );
    }

    /** @param array<string, mixed> $record */
    private function activityRow(string $investorType, string $dataType, string $interval, string $activityAt, array $record): array
    {
        $row = [
            'investor_type' => $investorType,
            'data_type' => $dataType,
            'interval' => $interval,
            'activity_at' => $activityAt,
            'synced_at' => now()->utc(),
            'created_at' => now()->utc(),
            'updated_at' => now()->utc(),
        ];

        foreach (['buy_amount', 'sell_amount', 'oi_amount'] as $field) {
            $row[$field] = $this->number($record[$field] ?? null);
        }
        foreach ([
            'buy_contracts', 'sell_contracts', 'oi_contracts', 'total_long_contracts',
            'total_short_contracts', 'total_call_long_contracts', 'total_put_long_contracts',
            'total_call_short_contracts', 'total_put_short_contracts',
        ] as $field) {
            $row[$field] = is_numeric($record[$field] ?? null) ? max(0, (int) $record[$field]) : null;
        }

        return $row;
    }

    private function string(mixed $value, int $length): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) ? (float) $value : null;
    }

    private function date(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            return Carbon::createFromFormat('!Y-m-d', $value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }
}
