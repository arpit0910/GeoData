<?php

namespace App\Services;

use App\Models\CorporateAction;
use App\Models\Equity;
use Carbon\Carbon;
use Illuminate\Support\Collection;
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
     * @return array{fetched: int, saved: int, errors: int}
     */
    public function syncForEquities(Collection $equities, ?array $allowedTypes = null): array
    {
        $stats = ['fetched' => 0, 'saved' => 0, 'errors' => 0];

        foreach ($equities as $equity) {
            try {
                $actions = $this->upstox->corporateActions($equity->isin);
                $stats['fetched'] += count($actions);

                foreach ($actions as $action) {
                    $type = strtoupper((string) ($action['type'] ?? 'EVENT'));

                    if ($allowedTypes !== null && !in_array($type, $allowedTypes, true)) {
                        continue;
                    }

                    $attributes = [
                        'isin' => $equity->isin,
                        'type' => $type,
                        'name' => $action['name'],
                        'expiry_date' => $action['expiry_date'],
                    ];

                    CorporateAction::updateOrCreate(
                        $attributes,
                        [
                            'symbol' => $equity->nse_symbol ?: $equity->bse_symbol,
                            'company_name' => $equity->company_name,
                            'record_date' => $action['record_date'] ?? null,
                            'announcement_date' => $action['announcement_date'] ?? null,
                            'ratio' => $action['ratio'] ?? null,
                            'amount' => $action['amount'] ?? null,
                            'details' => $action['details'] ?? null,
                            'raw_data' => $action['raw_data'] ?? null,
                        ]
                    );

                    $stats['saved']++;
                }
            } catch (Throwable $e) {
                $stats['errors']++;
                report($e);
            }
        }

        return $stats;
    }
}
