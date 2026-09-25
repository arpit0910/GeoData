<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Services\CompanyFundamentalsSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketDatasetController extends Controller
{
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
