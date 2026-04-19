<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MfApiController extends Controller
{
    private const VALID_PERIODS = ['1d','3d','7d','1m','3m','6m','9m','1y','3y'];

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/list
    // Query: search, category, type, amc_name, is_active, page, per_page
    // -------------------------------------------------------------------------
    public function list(Request $request): JsonResponse
    {
        $perPage = min((int)($request->per_page ?? 20), 100);

        $query = DB::table('mutual_funds as m')
            ->leftJoin(
                DB::raw('(
                    SELECT mfp.isin, mfp.nav, mfp.nav_date,
                           mfp.chg_1d, mfp.chg_1m, mfp.chg_3m, mfp.chg_6m, mfp.chg_1y, mfp.chg_3y
                    FROM mutual_fund_prices mfp
                    INNER JOIN (SELECT isin, MAX(nav_date) as max_date FROM mutual_fund_prices GROUP BY isin) latest
                        ON mfp.isin = latest.isin AND mfp.nav_date = latest.max_date
                ) as n'),
                'm.isin', '=', 'n.isin'
            )
            ->select(
                'm.isin', 'm.scheme_code', 'm.scheme_name', 'm.amc_name',
                'm.category', 'm.type', 'm.is_active',
                'n.nav', 'n.nav_date',
                'n.chg_1d', 'n.chg_1m', 'n.chg_3m', 'n.chg_6m', 'n.chg_1y', 'n.chg_3y'
            );

        if ($request->boolean('is_active', true)) {
            $query->where('m.is_active', 1);
        }

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('m.scheme_name', 'like', "%{$search}%")
                  ->orWhere('m.amc_name',   'like', "%{$search}%")
                  ->orWhere('m.isin',        'like', "%{$search}%");
            });
        }

        if ($cat  = $request->category)  $query->where('m.category', $cat);
        if ($type = $request->type)       $query->where('m.type', $type);
        if ($amc  = $request->amc_name)   $query->where('m.amc_name', $amc);

        $paginated = $query->orderBy('m.scheme_name')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $paginated->items(),
            'meta'    => [
                'total'        => $paginated->total(),
                'per_page'     => $paginated->perPage(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/search?q=
    // -------------------------------------------------------------------------
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        if ($q === '') {
            return response()->json(['success' => false, 'message' => 'Query parameter q is required.'], 400);
        }

        $limit = min((int)($request->limit ?? 20), 50);

        $results = DB::table('mutual_funds as m')
            ->leftJoin(
                DB::raw('(
                    SELECT mfp.isin, mfp.nav, mfp.nav_date, mfp.chg_1d, mfp.chg_1y
                    FROM mutual_fund_prices mfp
                    INNER JOIN (SELECT isin, MAX(nav_date) as max_date FROM mutual_fund_prices GROUP BY isin) latest
                        ON mfp.isin = latest.isin AND mfp.nav_date = latest.max_date
                ) as n'),
                'm.isin', '=', 'n.isin'
            )
            ->select('m.isin', 'm.scheme_code', 'm.scheme_name', 'm.amc_name', 'm.category', 'm.type',
                     'n.nav', 'n.nav_date', 'n.chg_1d', 'n.chg_1y')
            ->where('m.is_active', 1)
            ->where(function ($query) use ($q) {
                $query->where('m.scheme_name', 'like', "%{$q}%")
                      ->orWhere('m.amc_name',   'like', "%{$q}%")
                      ->orWhere('m.isin',        'like', "%{$q}%")
                      ->orWhere('m.scheme_code', 'like', "%{$q}%");
            })
            ->orderBy('m.scheme_name')
            ->limit($limit)
            ->get();

        return response()->json(['success' => true, 'count' => $results->count(), 'data' => $results]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/details/{isin}
    // Uses pre-computed chg_* / val_* columns — no runtime return computation
    // -------------------------------------------------------------------------
    public function details(string $isin): JsonResponse
    {
        $scheme = DB::table('mutual_funds as m')
            ->leftJoin(
                DB::raw('(
                    SELECT mfp.isin, mfp.nav, mfp.nav_date,
                           mfp.chg_1d, mfp.val_1d, mfp.chg_3d, mfp.val_3d,
                           mfp.chg_7d, mfp.val_7d, mfp.chg_1m, mfp.val_1m,
                           mfp.chg_3m, mfp.val_3m, mfp.chg_6m, mfp.val_6m,
                           mfp.chg_9m, mfp.val_9m, mfp.chg_1y, mfp.val_1y,
                           mfp.chg_3y, mfp.val_3y
                    FROM mutual_fund_prices mfp
                    INNER JOIN (SELECT isin, MAX(nav_date) as max_date FROM mutual_fund_prices GROUP BY isin) latest
                        ON mfp.isin = latest.isin AND mfp.nav_date = latest.max_date
                ) as n'),
                'm.isin', '=', 'n.isin'
            )
            ->select('m.*', 'n.nav as latest_nav', 'n.nav_date as latest_nav_date',
                     'n.chg_1d', 'n.val_1d', 'n.chg_3d', 'n.val_3d',
                     'n.chg_7d', 'n.val_7d', 'n.chg_1m', 'n.val_1m',
                     'n.chg_3m', 'n.val_3m', 'n.chg_6m', 'n.val_6m',
                     'n.chg_9m', 'n.val_9m', 'n.chg_1y', 'n.val_1y',
                     'n.chg_3y', 'n.val_3y')
            ->where('m.isin', $isin)
            ->first();

        if (!$scheme) {
            return response()->json(['success' => false, 'message' => 'Scheme not found.'], 404);
        }

        $returns = [];
        foreach (self::VALID_PERIODS as $p) {
            $chgKey = "chg_{$p}";
            $valKey = "val_{$p}";
            if ($scheme->$chgKey !== null) {
                $returns[$p] = [
                    'return_pct' => (float)$scheme->$chgKey,
                    'ref_nav'    => (float)$scheme->$valKey,
                ];
            }
            unset($scheme->$chgKey, $scheme->$valKey);
        }

        return response()->json([
            'success' => true,
            'data'    => array_merge((array)$scheme, ['returns' => $returns]),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/history/{isin}
    // Query: months (default 12, max 120), from, to, include_returns
    // -------------------------------------------------------------------------
    public function history(Request $request, string $isin): JsonResponse
    {
        $exists = DB::table('mutual_funds')->where('isin', $isin)->exists();
        if (!$exists) {
            return response()->json(['success' => false, 'message' => 'Scheme not found.'], 404);
        }

        if ($request->from && $request->to) {
            $from = $request->from;
            $to   = $request->to;
        } else {
            $months = min((int)($request->months ?? 12), 120);
            $from   = now()->subMonths($months)->startOfMonth()->format('Y-m-d');
            $to     = now()->format('Y-m-d');
        }

        $cols = ['nav_date', 'nav'];
        if ($request->boolean('include_returns')) {
            $cols = array_merge($cols, [
                'chg_1d','chg_7d','chg_1m','chg_3m','chg_6m','chg_1y','chg_3y',
            ]);
        }

        $data = DB::table('mutual_fund_prices')
            ->where('isin', $isin)
            ->whereBetween('nav_date', [$from, $to])
            ->orderBy('nav_date')
            ->select($cols)
            ->get();

        return response()->json([
            'success' => true,
            'isin'    => $isin,
            'from'    => $from,
            'to'      => $to,
            'count'   => $data->count(),
            'data'    => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/analysis/top-gainers
    // Query: period (default 1y), category, amc_name, limit (max 50)
    // -------------------------------------------------------------------------
    public function topGainers(Request $request): JsonResponse
    {
        return $this->topByPeriod($request, 'desc');
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/analysis/top-losers
    // -------------------------------------------------------------------------
    public function topLosers(Request $request): JsonResponse
    {
        return $this->topByPeriod($request, 'asc');
    }

    private function topByPeriod(Request $request, string $direction): JsonResponse
    {
        $period = $request->get('period', '1y');
        if (!in_array($period, self::VALID_PERIODS)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid period. Valid: ' . implode(', ', self::VALID_PERIODS),
            ], 422);
        }

        $limit      = min((int)($request->limit ?? 10), 50);
        $latestDate = DB::table('mutual_fund_prices')->max('nav_date');

        $query = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $latestDate)
            ->whereNotNull("p.chg_{$period}")
            ->select(
                'm.isin', 'm.scheme_name', 'm.amc_name', 'm.category',
                'p.nav', 'p.nav_date',
                DB::raw("p.chg_{$period} as return_pct"),
                DB::raw("p.val_{$period} as ref_nav")
            );

        if ($cat = $request->category) $query->where('m.category', $cat);
        if ($amc = $request->amc_name) $query->where('m.amc_name', $amc);

        $data = $query->orderBy("p.chg_{$period}", $direction)->limit($limit)->get();

        return response()->json([
            'success'  => true,
            'period'   => $period,
            'nav_date' => $latestDate,
            'data'     => $data,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/compare?isins=ISIN1,ISIN2,ISIN3
    // Compare up to 5 funds side by side across all return periods
    // -------------------------------------------------------------------------
    public function compare(Request $request): JsonResponse
    {
        $raw   = $request->get('isins', '');
        $isins = array_values(array_filter(array_map('trim', explode(',', $raw))));

        if (count($isins) < 2) {
            return response()->json(['success' => false, 'message' => 'Provide at least 2 ISINs separated by commas.'], 422);
        }
        if (count($isins) > 5) {
            return response()->json(['success' => false, 'message' => 'Maximum 5 ISINs allowed.'], 422);
        }

        $latestDate = DB::table('mutual_fund_prices')->max('nav_date');

        $rows = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->whereIn('p.isin', $isins)
            ->where('p.nav_date', $latestDate)
            ->select(
                'm.isin', 'm.scheme_name', 'm.amc_name', 'm.category',
                'p.nav', 'p.nav_date',
                'p.chg_1d', 'p.chg_3d', 'p.chg_7d',
                'p.chg_1m', 'p.chg_3m', 'p.chg_6m', 'p.chg_9m',
                'p.chg_1y', 'p.chg_3y'
            )
            ->get();

        $notFound = array_values(array_diff($isins, $rows->pluck('isin')->all()));

        return response()->json([
            'success'   => true,
            'nav_date'  => $latestDate,
            'not_found' => $notFound,
            'data'      => $rows,
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/filters
    // -------------------------------------------------------------------------
    public function filters(): JsonResponse
    {
        return response()->json([
            'success'    => true,
            'categories' => DB::table('mutual_funds')->distinct()->whereNotNull('category')->orderBy('category')->pluck('category'),
            'types'      => DB::table('mutual_funds')->distinct()->whereNotNull('type')->orderBy('type')->pluck('type'),
            'amcs'       => DB::table('mutual_funds')->distinct()->whereNotNull('amc_name')->orderBy('amc_name')->pluck('amc_name'),
        ]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/analysis/category-returns
    // Average returns grouped by fund category for every period.
    // One-call market summary — see if Equity is beating Debt, Hybrid, ETF, etc.
    // -------------------------------------------------------------------------
    public function categoryReturns(Request $request): JsonResponse
    {
        $latestDate = DB::table('mutual_fund_prices')->max('nav_date');

        $data = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $latestDate)
            ->whereNotNull('m.category')
            ->groupBy('m.category')
            ->orderBy('m.category')
            ->select(
                'm.category',
                DB::raw('COUNT(*) as fund_count'),
                DB::raw('ROUND(AVG(p.chg_1d),  4) as avg_return_1d'),
                DB::raw('ROUND(AVG(p.chg_7d),  4) as avg_return_7d'),
                DB::raw('ROUND(AVG(p.chg_1m),  4) as avg_return_1m'),
                DB::raw('ROUND(AVG(p.chg_3m),  4) as avg_return_3m'),
                DB::raw('ROUND(AVG(p.chg_6m),  4) as avg_return_6m'),
                DB::raw('ROUND(AVG(p.chg_1y),  4) as avg_return_1y'),
                DB::raw('ROUND(AVG(p.chg_3y),  4) as avg_return_3y'),
                DB::raw('ROUND(MAX(p.chg_1y),  4) as best_1y'),
                DB::raw('ROUND(MIN(p.chg_1y),  4) as worst_1y')
            )
            ->get();

        return response()->json(['success' => true, 'nav_date' => $latestDate, 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/analysis/amc-performance
    // Average returns grouped by AMC (fund house).
    // Tells users which fund house is delivering the best results.
    // Query: period=1y, limit=20
    // -------------------------------------------------------------------------
    public function amcPerformance(Request $request): JsonResponse
    {
        $period = $request->get('period', '1y');
        if (!in_array($period, self::VALID_PERIODS)) {
            return response()->json(['success' => false, 'message' => 'Invalid period. Valid: ' . implode(', ', self::VALID_PERIODS)], 422);
        }

        $limit      = min((int)($request->limit ?? 20), 50);
        $latestDate = DB::table('mutual_fund_prices')->max('nav_date');
        $category   = $request->get('category');

        $query = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $latestDate)
            ->whereNotNull('m.amc_name')
            ->whereNotNull("p.chg_{$period}")
            ->groupBy('m.amc_name')
            ->orderBy('avg_return', 'desc')
            ->select(
                'm.amc_name',
                DB::raw('COUNT(*) as fund_count'),
                DB::raw("ROUND(AVG(p.chg_{$period}), 4) as avg_return"),
                DB::raw("ROUND(MAX(p.chg_{$period}), 4) as best_fund_return"),
                DB::raw("ROUND(MIN(p.chg_{$period}), 4) as worst_fund_return"),
                DB::raw("SUM(CASE WHEN p.chg_{$period} > 0 THEN 1 ELSE 0 END) as positive_funds")
            );

        if ($category) $query->where('m.category', $category);

        $data = $query->limit($limit)->get();

        return response()->json(['success' => true, 'period' => $period, 'nav_date' => $latestDate, 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/analysis/consistent-performers
    // Funds that are positive across all requested periods simultaneously.
    // Strong filter for identifying steady, low-volatility compounders.
    // Query: periods=1m,3m,6m,1y  category=Equity  limit=20
    // -------------------------------------------------------------------------
    public function consistentPerformers(Request $request): JsonResponse
    {
        $periodsParam = $request->get('periods', '1m,3m,6m,1y');
        $requestedPeriods = array_filter(
            array_map('trim', explode(',', $periodsParam)),
            fn($p) => in_array($p, self::VALID_PERIODS)
        );

        if (empty($requestedPeriods)) {
            return response()->json(['success' => false, 'message' => 'No valid periods specified.'], 422);
        }

        $limit      = min((int)($request->limit ?? 20), 100);
        $latestDate = DB::table('mutual_fund_prices')->max('nav_date');

        $query = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $latestDate);

        foreach ($requestedPeriods as $period) {
            $query->where("p.chg_{$period}", '>', 0);
        }

        if ($cat = $request->category) $query->where('m.category', $cat);
        if ($amc = $request->amc_name) $query->where('m.amc_name', $amc);

        $selectCols = ['m.isin', 'm.scheme_name', 'm.amc_name', 'm.category', 'p.nav', 'p.nav_date'];
        foreach ($requestedPeriods as $period) {
            $selectCols[] = "p.chg_{$period}";
        }

        $data = $query->select($selectCols)->orderBy('p.chg_1y', 'desc')->limit($limit)->get();

        return response()->json(['success' => true, 'periods' => array_values($requestedPeriods), 'nav_date' => $latestDate, 'data' => $data]);
    }

    // -------------------------------------------------------------------------
    // GET /api/v1/mf/{isin}/similar-funds
    // Funds in the same category with similar 1Y return profile (±10% range).
    // Discovery feature — helps users find alternatives to a fund they're evaluating.
    // Query: limit=10
    // -------------------------------------------------------------------------
    public function similarFunds(Request $request, string $isin): JsonResponse
    {
        $scheme = DB::table('mutual_funds as m')
            ->leftJoin(
                DB::raw('(SELECT mfp.isin, mfp.nav, mfp.nav_date, mfp.chg_1y FROM mutual_fund_prices mfp INNER JOIN (SELECT isin, MAX(nav_date) as max_date FROM mutual_fund_prices GROUP BY isin) latest ON mfp.isin = latest.isin AND mfp.nav_date = latest.max_date) as n'),
                'm.isin', '=', 'n.isin'
            )
            ->where('m.isin', $isin)
            ->select('m.isin', 'm.scheme_name', 'm.category', 'm.amc_name', 'n.chg_1y', 'n.nav_date')
            ->first();

        if (!$scheme) return response()->json(['success' => false, 'message' => 'Scheme not found.'], 404);
        if (!$scheme->category) return response()->json(['success' => false, 'message' => 'No category data for this scheme.'], 404);

        $limit  = min((int)($request->limit ?? 10), 30);
        $return = (float)($scheme->chg_1y ?? 0);
        $band   = 10;

        $similar = DB::table('mutual_fund_prices as p')
            ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
            ->where('p.nav_date', $scheme->nav_date)
            ->where('m.category', $scheme->category)
            ->where('m.isin', '!=', $isin)
            ->whereBetween('p.chg_1y', [$return - $band, $return + $band])
            ->select('m.isin', 'm.scheme_name', 'm.amc_name', 'p.nav', 'p.nav_date', 'p.chg_1d', 'p.chg_1m', 'p.chg_1y', 'p.chg_3y')
            ->orderByRaw("ABS(p.chg_1y - {$return})")
            ->limit($limit)
            ->get();

        return response()->json(['success' => true, 'isin' => $isin, 'scheme_name' => $scheme->scheme_name, 'category' => $scheme->category, 'data' => $similar]);
    }
}
