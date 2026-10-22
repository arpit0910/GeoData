<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MutualFundNudge;
use App\Models\StockNudge;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class NudgeController extends Controller
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

        return $this->respond(
            $request,
            StockNudge::query()->where('isin', $isin),
            ['isin' => $isin],
            all: true,
            createdAt: true
        );
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

    public function mutualFunds(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'isin' => ['nullable', 'regex:/^[A-Za-z]{2}[A-Za-z0-9]{9}[0-9]$/'],
            'category' => ['nullable', 'in:growth,fall'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $isin = isset($validated['isin']) ? strtoupper($validated['isin']) : null;
        $query = MutualFundNudge::query()
            ->with('fund:id,isin,scheme_name,amc_name,category')
            ->when($isin, fn ($query) => $query->where('isin', $isin))
            ->when($validated['category'] ?? null, fn ($query, $category) => $query->whereIn('rule', MutualFundNudge::CATEGORY_RULES[$category]));

        if (isset($validated['date'])) {
            $query->whereDate('nav_date', $validated['date']);
            $dateFrom = $dateTo = $validated['date'];
        } else {
            $dateTo = now('Asia/Kolkata')->toDateString();
            $dateFrom = now('Asia/Kolkata')->subDays(6)->toDateString();
            $query->whereDate('nav_date', '>=', $dateFrom)
                ->whereDate('nav_date', '<=', $dateTo);
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $nudges = $query->latest('nav_date')->latest('id')->paginate($perPage);
        $nudges->setCollection($nudges->getCollection()->map(fn (MutualFundNudge $nudge) => [
            'id' => $nudge->id,
            'isin' => $nudge->isin,
            'scheme_name' => $nudge->fund?->scheme_name,
            'amc_name' => $nudge->fund?->amc_name,
            'fund_category' => $nudge->fund?->category,
            'category' => MutualFundNudge::categoryForRule($nudge->rule),
            'rule' => $nudge->rule,
            'severity' => $nudge->severity,
            'title' => $nudge->title,
            'message' => $nudge->message,
            'observed_nav' => $nudge->observed_nav,
            'metrics' => $nudge->metrics,
            'nav_date' => $nudge->nav_date?->toDateString(),
            'observed_at' => $nudge->observed_at?->copy()->timezone('Asia/Kolkata')->toIso8601String(),
        ]));

        return response()->json([
            'success' => true,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'data' => $nudges->items(),
            'pagination' => $this->pagination($nudges),
        ]);
    }

    private function respond(Request $request, Builder $query, array $context = [], bool $all = false, bool $createdAt = false): JsonResponse
    {
        $validated = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        if (isset($validated['date'])) {
            $dateFrom = $dateTo = $validated['date'];
            if ($createdAt) {
                $start = Carbon::parse($validated['date'], 'Asia/Kolkata')->startOfDay()->utc();
                $query->where('created_at', '>=', $start)
                    ->where('created_at', '<', $start->copy()->addDay());
            } else {
                $query->whereDate('trading_date', $validated['date']);
            }
        } else {
            $dateTo = now('Asia/Kolkata')->toDateString();
            $dateFrom = now('Asia/Kolkata')->subDays(6)->toDateString();
            if ($createdAt) {
                $start = now('Asia/Kolkata')->subDays(6)->startOfDay()->utc();
                $end = now('Asia/Kolkata')->addDay()->startOfDay()->utc();
                $query->where('created_at', '>=', $start)
                    ->where('created_at', '<', $end);
            } else {
                $query->whereDate('trading_date', '>=', $dateFrom)
                    ->whereDate('trading_date', '<=', $dateTo);
            }
        }

        $perPage = min(max((int) $request->input('per_page', 25), 1), 100);
        $query->latest('observed_at')->latest('id');
        if ($all) {
            $rows = $query->get();
            $pagination = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => $rows->count(),
                'total' => $rows->count(),
            ];
        } else {
            $paginator = $query->paginate($perPage);
            $rows = $paginator->getCollection();
            $pagination = $this->pagination($paginator);
        }
        $data = $rows->map(fn (StockNudge $nudge) => [
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
            'created_at' => $nudge->created_at?->copy()->timezone('Asia/Kolkata')->toIso8601String(),
        ])->values();

        return response()->json(array_merge([
            'success' => true,
            'date' => $dateTo,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ], $context, [
            'data' => $data,
            'pagination' => $pagination,
        ]));
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }
}
