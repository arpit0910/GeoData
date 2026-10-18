<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Models\InstitutionalActivity;
use App\Models\Ipo;
use App\Services\CompanyFundamentalsSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketDatasetController extends Controller
{
    public function ipos(Request $request): JsonResponse
    {
        $status = strtolower(trim((string) $request->input('status', '')));
        $issueType = strtolower(trim((string) $request->input('issue_type', '')));
        if ($status !== '' && !in_array($status, ['upcoming', 'open', 'closed', 'listed'], true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported IPO status.'], 422);
        }
        if ($issueType !== '' && !in_array($issueType, ['regular', 'sme'], true)) {
            return response()->json(['success' => false, 'message' => 'issue_type must be regular or sme.'], 422);
        }

        $query = Ipo::query();

        foreach (['status', 'issue_type'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, strtolower(trim((string) $request->input($filter))));
            }
        }
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('symbol', 'like', "%{$search}%")
                ->orWhere('industry', 'like', "%{$search}%"));
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $ipos = $query
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'upcoming' THEN 2 WHEN 'closed' THEN 3 ELSE 4 END")
            ->orderByDesc('bidding_start_date')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($ipos->items())->map(fn (Ipo $ipo) => $this->ipoData($ipo))->all(),
            'pagination' => $this->pagination($ipos),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function ipo(string $slug): JsonResponse
    {
        $ipo = Ipo::query()->where('slug', $slug)->first();
        if (!$ipo) {
            return response()->json(['success' => false, 'message' => 'IPO not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->ipoData($ipo) + ['details' => $this->sanitizePayload($ipo->details)],
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function institutionalActivity(Request $request): JsonResponse
    {
        $investorType = strtolower(trim((string) $request->input('investor_type', '')));
        $interval = strtoupper(trim((string) $request->input('interval', '1D')));
        if ($investorType !== '' && !in_array($investorType, ['fii', 'dii'], true)) {
            return response()->json(['success' => false, 'message' => 'investor_type must be fii or dii.'], 422);
        }
        if (!in_array($interval, ['1D', '1M'], true)) {
            return response()->json(['success' => false, 'message' => 'interval must be 1D or 1M.'], 422);
        }
        foreach (['from', 'to'] as $dateField) {
            if ($request->filled($dateField) && !$this->validDate((string) $request->input($dateField))) {
                return response()->json(['success' => false, 'message' => "{$dateField} must use YYYY-MM-DD format."], 422);
            }
        }

        $query = InstitutionalActivity::query()->where('interval', $interval);
        if ($investorType !== '') {
            $query->where('investor_type', $investorType);
        }
        if ($request->filled('data_type')) {
            $query->where('data_type', trim((string) $request->input('data_type')));
        }
        if ($request->filled('from')) {
            $query->whereDate('activity_at', '>=', trim((string) $request->input('from')));
        }
        if ($request->filled('to')) {
            $query->whereDate('activity_at', '<=', trim((string) $request->input('to')));
        }

        $perPage = min(max((int) $request->input('per_page', 50), 1), 100);
        $records = $query->orderByDesc('activity_at')->orderBy('investor_type')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($records->items())->map(function (InstitutionalActivity $record) {
                $buy = $record->buy_amount;
                $sell = $record->sell_amount;

                return [
                    'investor_type' => strtoupper($record->investor_type),
                    'data_type' => $record->data_type,
                    'interval' => $record->interval,
                    'activity_at' => $record->activity_at?->toIso8601String(),
                    'buy_amount' => $buy,
                    'sell_amount' => $sell,
                    'net_amount' => $buy !== null && $sell !== null ? round($buy - $sell, 4) : null,
                    'buy_contracts' => $record->buy_contracts,
                    'sell_contracts' => $record->sell_contracts,
                    'oi_contracts' => $record->oi_contracts,
                    'oi_amount' => $record->oi_amount,
                    'total_long_contracts' => $record->total_long_contracts,
                    'total_short_contracts' => $record->total_short_contracts,
                    'total_call_long_contracts' => $record->total_call_long_contracts,
                    'total_put_long_contracts' => $record->total_put_long_contracts,
                    'total_call_short_contracts' => $record->total_call_short_contracts,
                    'total_put_short_contracts' => $record->total_put_short_contracts,
                    'synced_at' => $record->synced_at?->toIso8601String(),
                ];
            })->all(),
            'pagination' => $this->pagination($records),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/v1/market/global-instruments
     */
    public function globalInstruments(Request $request): JsonResponse
    {
        $query = GlobalInstrument::query()->where('is_active', true);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('trading_symbol', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%"));
        }

        foreach (['segment', 'country', 'instrument_type'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, trim((string) $request->input($filter)));
            }
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $instruments = $query->orderBy('name')->paginate($perPage);

        $data = collect($instruments->items())->map(fn (GlobalInstrument $instrument) => [
            'id' => $instrument->id,
            'name' => $instrument->name,
            'trading_symbol' => $instrument->trading_symbol,
            'segment' => $instrument->segment,
            'exchange' => $instrument->exchange,
            'country' => $instrument->country,
            'instrument_type' => $instrument->instrument_type,
            'latency' => $instrument->latency,
            'trading_hours' => [
                'start' => $instrument->start_time,
                'end' => $instrument->end_time,
                'week_days' => $instrument->week_days,
            ],
            'synced_at' => $instrument->synced_at?->toIso8601String(),
        ])->all();

        return response()->json([
            'success' => true,
            'data' => $data,
            'pagination' => $this->pagination($instruments),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/v1/market/company-fundamentals/{isin}
     */
    public function companyFundamentals(Request $request, string $isin): JsonResponse
    {
        $isin = strtoupper(trim($isin));
        if (! preg_match('/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/', $isin)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ISIN format. ISIN must contain 12 alphanumeric characters.',
            ], 422);
        }

        $datasetInput = $request->input('dataset', '');
        $datasetValues = is_array($datasetInput)
            ? $datasetInput
            : preg_split('/[\s,]+/', (string) $datasetInput);
        $datasets = collect($datasetValues)
            ->map(fn ($dataset) => strtolower(trim((string) $dataset)))
            ->filter()->unique()->values();
        $invalidDatasets = $datasets->diff(CompanyFundamentalsSyncService::DATASETS);
        if ($invalidDatasets->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported dataset selection.',
                'allowed_datasets' => CompanyFundamentalsSyncService::DATASETS,
            ], 422);
        }

        $query = CompanyFundamental::query()->where('isin', $isin);
        if ($datasets->isNotEmpty()) {
            $query->whereIn('dataset', $datasets->all());
        }
        foreach (['statement_type', 'time_period'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, strtolower(trim((string) $request->input($filter))));
            }
        }

        $records = $query
            ->orderBy('dataset')
            ->orderBy('statement_type')
            ->orderBy('time_period')
            ->get();

        if ($records->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => "No company fundamentals found for ISIN '{$isin}'.",
            ], 404);
        }

        $equity = Equity::query()->where('isin', $isin)->first();

        return response()->json([
            'success' => true,
            'isin' => $isin,
            'company' => [
                'name' => $equity?->company_name,
                'nse_symbol' => $equity?->nse_symbol,
                'bse_symbol' => $equity?->bse_symbol,
                'industry' => $equity?->industry,
                'sector' => $equity?->sector,
            ],
            'available_datasets' => $records->pluck('dataset')->unique()->values(),
            'data' => $records->map(fn (CompanyFundamental $record) => [
                'dataset' => $record->dataset,
                'statement_type' => $record->statement_type,
                'time_period' => $record->time_period,
                'payload' => $this->sanitizePayload($record->payload),
                'synced_at' => $record->synced_at?->toIso8601String(),
            ])->values(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem() ?? 0,
            'to' => $paginator->lastItem() ?? 0,
        ];
    }

    private function ipoData(Ipo $ipo): array
    {
        return [
            'slug' => $ipo->slug,
            'symbol' => $ipo->symbol,
            'name' => $ipo->name,
            'status' => $ipo->status,
            'isin' => $ipo->isin,
            'issue_type' => $ipo->issue_type,
            'issue_size' => $ipo->issue_size,
            'industry' => $ipo->industry,
            'minimum_price' => $ipo->minimum_price,
            'maximum_price' => $ipo->maximum_price,
            'bidding_start_date' => $ipo->bidding_start_date?->format('Y-m-d'),
            'bidding_end_date' => $ipo->bidding_end_date?->format('Y-m-d'),
            'total_subscription' => $ipo->total_subscription,
            'synced_at' => $ipo->synced_at?->toIso8601String(),
        ];
    }

    private function validDate(string $value): bool
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }

    private function sanitizePayload(mixed $value): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $normalizedKey = strtolower((string) $key);
                if (str_contains($normalizedKey, 'instrument_key')
                    || str_contains($normalizedKey, 'instrument_token')
                    || str_contains($normalizedKey, 'access_token')
                    || str_contains($normalizedKey, 'provider')
                    || str_contains($normalizedKey, 'upstox')) {
                    continue;
                }

                $sanitized[$key] = $this->sanitizePayload($item);
            }

            return $sanitized;
        }

        if (is_string($value)) {
            return preg_replace('/upstox/i', 'market data source', $value);
        }

        return $value;
    }
}
