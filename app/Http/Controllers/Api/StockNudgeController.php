<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockNudge;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class StockNudgeController extends Controller
{
    public function byIsin(Request $request, string $isin): JsonResponse
    {
        $isin = strtoupper(trim($isin));
        if (! preg_match('/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/', $isin)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid ISIN format. ISIN must contain 12 alphanumeric characters.',
            ], 422);
        }

        return $this->respond($request, StockNudge::query()->where('isin', $isin), ['isin' => $isin]);
    }

    public function byIndex(Request $request, string $indexCode): JsonResponse
    {
        $indexCode = strtoupper(trim($indexCode));
        if ($indexCode === '' || strlen($indexCode) > 255) {
            return response()->json(['success' => false, 'message' => 'Invalid index code.'], 422);
        }

        return $this->respond(
            $request,
            StockNudge::query()->where(function ($query) use ($indexCode) {
                $query->where('index_code', $indexCode)
                    ->orWhere('metrics->benchmark_index_code', $indexCode);
            }),
            ['index_code' => $indexCode]
        );
    }

    public function fresh(Request $request): JsonResponse
    {
        return $this->respond($request, StockNudge::query());
    }

    public function byCategory(Request $request, string $category): JsonResponse
    {
        $category = strtolower(trim($category));
        $rules = StockNudge::rulesForCategory($category);
        if ($rules === null) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid nudge category.',
                'allowed_categories' => array_keys(StockNudge::CATEGORY_RULES),
            ], 422);
        }

        return $this->respond(
            $request,
            StockNudge::query()->whereIn('rule', $rules),
            ['category' => $category]
        );
    }

    private function respond(Request $request, Builder $query, array $context = []): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $latestDate = isset($validated['date']) ? null : (clone $query)->max('trading_date');
        $date = $validated['date'] ?? ($latestDate ? Carbon::parse($latestDate)->toDateString() : null);
        if ($date !== null) {
            $query->whereDate('trading_date', $date);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $nudges = $query->latest('observed_at')->latest('id')->paginate($perPage);
        $nudges->setCollection($nudges->getCollection()->map(fn (StockNudge $nudge) => [
            'id' => $nudge->id,
            'isin' => $nudge->isin,
            'index_code' => $nudge->index_code,
            'instrument_type' => $nudge->index_code ? 'index' : 'stock',
            'exchange' => $nudge->exchange,
            'source' => $nudge->type,
            'category' => StockNudge::categoryForRule($nudge->rule),
            'rule' => $nudge->rule,
            'severity' => $nudge->severity,
            'title' => $nudge->title,
            'message' => $nudge->message,
            'observed_price' => $nudge->observed_price,
            'metrics' => $nudge->metrics,
            'trading_date' => $nudge->trading_date?->toDateString(),
            'observed_at' => $nudge->observed_at?->copy()->timezone('Asia/Kolkata')->toIso8601String(),
            'expires_at' => $nudge->expires_at?->copy()->timezone('Asia/Kolkata')->toIso8601String(),
        ]));

        return response()->json(array_merge([
            'success' => true,
            'date' => $date,
        ], $context, [
            'data' => $nudges->items(),
            'pagination' => [
                'current_page' => $nudges->currentPage(),
                'last_page' => $nudges->lastPage(),
                'per_page' => $nudges->perPage(),
                'total' => $nudges->total(),
            ],
        ]));
    }

}
