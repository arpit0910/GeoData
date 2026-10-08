<?php

namespace App\Services;

use App\Models\CorporateAction;
use App\Models\Equity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class UpstoxCorporateActionSyncService
{
    public function __construct(
        protected UpstoxMarketDataService $upstox
    ) {}

    /**
     * Sync corporate actions for equities filtered by specific action types.
     *
     * @param Collection<int, Equity> $equities
     * @param array<int, string>|null $allowedTypes e.g. ['SPLIT'], ['BONUS'], ['EVENT', 'DIVIDEND']
     * @return array{companies: int, fetched: int, saved: int, errors: int, messages: array<int, string>}
     */
    public function syncForEquities(Collection $equities, ?array $allowedTypes = null, int $delayMs = 0): array
    {
        $syncTrackingEnabled = $this->syncTrackingEnabled();
        $stats = [
            'companies' => 0,
            'fetched' => 0,
            'saved' => 0,
            'errors' => 0,
            'messages' => [],
        ];

        foreach ($equities->values() as $index => $equity) {
            $stats['companies']++;
            try {
                $actions = $this->upstox->corporateActions($equity->isin);
                $stats['fetched'] += count($actions);

                foreach ($actions as $action) {
                    $type = strtoupper((string) ($action['type'] ?? 'EVENT'));

                    if ($allowedTypes !== null && !in_array($type, $allowedTypes, true)) {
                        continue;
                    }

                    $existing = CorporateAction::query()
                        ->where('isin', $equity->isin)
                        ->where('type', $type)
                        ->where('name', $action['name'])
                        ->when(
                            $action['expiry_date'] ?? null,
                            fn ($query, $date) => $query->whereDate('expiry_date', $date),
                            fn ($query) => $query->whereNull('expiry_date')
                        )
                        ->first();

                    ($existing ?: new CorporateAction())->fill([
                        'isin' => $equity->isin,
                        'type' => $type,
                        'name' => $action['name'],
                        'expiry_date' => $action['expiry_date'],
                        'symbol' => $equity->nse_symbol ?: $equity->bse_symbol,
                        'company_name' => $equity->company_name,
                        'record_date' => $action['record_date'] ?? null,
                        'announcement_date' => $action['announcement_date'] ?? null,
                        'ratio' => $action['ratio'] ?? null,
                        'amount' => $action['amount'] ?? null,
                        'details' => $action['details'] ?? null,
                        'raw_data' => $action['raw_data'] ?? null,
                    ])->save();

                    $stats['saved']++;
                }

                if ($allowedTypes === null && $syncTrackingEnabled) {
                    $equity->forceFill([
                        'corporate_actions_sync_attempted_at' => now()->utc(),
                        'corporate_actions_synced_at' => now()->utc(),
                        'corporate_actions_sync_error' => null,
                    ])->save();
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                $stats['messages'][] = $equity->isin.': '.$e->getMessage();
                if ($allowedTypes === null && $syncTrackingEnabled) {
                    $equity->forceFill([
                        'corporate_actions_sync_attempted_at' => now()->utc(),
                        'corporate_actions_sync_error' => mb_substr($e->getMessage(), 0, 2000),
                    ])->save();
                }
                report($e);

                if (preg_match('/HTTP (401|403)\b/', $e->getMessage()) === 1
                    || str_contains($e->getMessage(), 'No active Upstox token')) {
                    break;
                }
            }

            if ($delayMs > 0 && $index < $equities->count() - 1) {
                usleep($delayMs * 1000);
            }
        }

        return $stats;
    }

    private function syncTrackingEnabled(): bool
    {
        return Schema::hasColumn('equities', 'corporate_actions_sync_attempted_at')
            && Schema::hasColumn('equities', 'corporate_actions_synced_at')
            && Schema::hasColumn('equities', 'corporate_actions_sync_error');
    }
}
