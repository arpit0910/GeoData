<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MutualFundDetailsService
{
    private const RETURN_PERIODS = ['1d', '3d', '7d', '1m', '3m', '6m', '9m', '1y', '3y'];

    private const UPSTOX_FIELDS = [
        'instrument_key',
        'amc',
        'purchase_allowed',
        'redemption_allowed',
        'minimum_purchase_amount',
        'maximum_purchase_amount',
        'purchase_amount_multiplier',
        'additional_purchase_amount',
        'minimum_redemption_quantity',
        'maximum_redemption_quantity',
        'redemption_quantity_multiplier',
        'minimum_redemption_amount',
        'maximum_redemption_amount',
        'redemption_amount_multiplier',
        'dividend_type',
        'scheme_type',
        'plan',
        'settlement_type',
        'hair_cut',
    ];

    public function find(string $isin): ?array
    {
        $isin = strtoupper(trim($isin));
        $scheme = DB::table('mutual_funds')
            ->where(function ($query) use ($isin) {
                $query->where('isin', $isin)
                    ->orWhere('isin_reinvest', $isin);
            })
            ->first();
        if (! $scheme) {
            return null;
        }

        // NAV rows are stored against the scheme's primary ISIN, even when the
        // lookup used the scheme's reinvestment ISIN.
        $priceIsin = $scheme->isin;

        $latestNav = DB::table('mutual_fund_prices')
            ->where('isin', $priceIsin)
            ->orderByDesc('nav_date')
            ->first();
        $history = DB::table('mutual_fund_prices')
            ->where('isin', $priceIsin)
            ->selectRaw('COUNT(*) as observations, MIN(nav_date) as first_date, MAX(nav_date) as last_date, MIN(nav) as lowest_nav, MAX(nav) as highest_nav')
            ->first();
        $recentNavs = DB::table('mutual_fund_prices')
            ->where('isin', $priceIsin)
            ->orderByDesc('nav_date')
            ->limit(12)
            ->get(['nav_date', 'nav'])
            ->reverse()
            ->values()
            ->map(fn ($row) => [
                'date' => $row->nav_date,
                'nav' => (float) $row->nav,
            ])
            ->all();

        $returns = [];
        foreach (self::RETURN_PERIODS as $period) {
            $change = "chg_{$period}";
            $reference = "val_{$period}";
            if ($latestNav?->$change !== null) {
                $returns[$period] = [
                    'return_pct' => (float) $latestNav->$change,
                    'ref_nav' => $latestNav->$reference !== null ? (float) $latestNav->$reference : null,
                ];
            }
        }

        $metadata = json_decode((string) ($scheme->upstox_metadata ?? ''), true);
        $metadata = is_array($metadata) ? $metadata : [];
        $transactionDetails = [];
        foreach (self::UPSTOX_FIELDS as $field) {
            if (array_key_exists($field, $metadata)) {
                $transactionDetails[$field] = $metadata[$field];
            }
        }

        return [
            'id' => (int) $scheme->id,
            'requested_isin' => $isin,
            'isin' => $scheme->isin,
            'scheme_code' => $scheme->scheme_code,
            'isin_reinvest' => $scheme->isin_reinvest,
            'scheme_name' => $scheme->scheme_name,
            'amc_name' => $scheme->amc_name,
            'category' => $scheme->category,
            'sub_category' => $scheme->sub_category,
            'type' => $scheme->type,
            'is_active' => (bool) $scheme->is_active,
            'created_at' => $scheme->created_at,
            'updated_at' => $scheme->updated_at,
            'latest_nav' => $latestNav ? (float) $latestNav->nav : null,
            'latest_nav_date' => $latestNav?->nav_date,
            'returns' => $returns,
            'nav_history' => [
                'observations' => (int) ($history->observations ?? 0),
                'first_date' => $history->first_date ?? null,
                'last_date' => $history->last_date ?? null,
                'lowest_nav' => $history?->lowest_nav !== null ? (float) $history->lowest_nav : null,
                'highest_nav' => $history?->highest_nav !== null ? (float) $history->highest_nav : null,
                'recent' => $recentNavs,
            ],
            'transaction_details' => $transactionDetails,
            'holdings' => [
                'available' => false,
                'as_of_date' => null,
                'data' => [],
                'reason' => 'AMC monthly portfolio disclosure ingestion is not configured yet.',
            ],
            'sector_allocation' => [
                'available' => false,
                'as_of_date' => null,
                'data' => [],
                'reason' => 'Sector allocation will be derived from AMC monthly portfolio disclosures.',
            ],
            'sources' => [
                'scheme' => 'AMFI',
                'nav' => 'AMFI and public mutual-fund instrument data',
                'transaction_details' => $transactionDetails !== [] ? 'Public mutual-fund instrument data' : null,
                'portfolio' => null,
                'upstox_synced_at' => $scheme->upstox_synced_at,
            ],
        ];
    }
}
