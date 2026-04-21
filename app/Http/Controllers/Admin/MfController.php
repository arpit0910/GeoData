<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MfMaster;
use App\Models\MfNavHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MfController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Count and filter on mutual_funds only — never on the expensive NAV join.
            $baseQuery = DB::table('mutual_funds as m');

            if ($search = $request->input('search.value')) {
                $baseQuery->where(function ($q) use ($search) {
                    $q->where('m.scheme_name', 'like', "%{$search}%")
                      ->orWhere('m.amc_name',   'like', "%{$search}%")
                      ->orWhere('m.isin',        'like', "%{$search}%");
                });
            }

            $total    = DB::table('mutual_funds')->count();
            $filtered = (clone $baseQuery)->count();
            $limit    = max(1, (int) ($request->length ?? 25));
            $start    = max(0, (int) ($request->start  ?? 0));

            // Fetch just the paginated ISINs first.
            $isins = (clone $baseQuery)
                ->orderBy('m.scheme_name')
                ->skip($start)
                ->take($limit)
                ->pluck('m.isin')
                ->toArray();

            if (empty($isins)) {
                return response()->json([
                    'draw'            => intval($request->draw),
                    'recordsTotal'    => $total,
                    'recordsFiltered' => $filtered,
                    'data'            => [],
                ]);
            }

            // Fetch latest NAV for only these ~25 ISINs — fast.
            $latestNavs = DB::table('mutual_fund_prices as p')
                ->joinSub(
                    DB::table('mutual_fund_prices')
                        ->whereIn('isin', $isins)
                        ->selectRaw('isin, MAX(nav_date) as max_date')
                        ->groupBy('isin'),
                    'l',
                    fn($j) => $j->on('p.isin', '=', 'l.isin')->on('p.nav_date', '=', 'l.max_date')
                )
                ->whereIn('p.isin', $isins)
                ->select('p.isin', 'p.nav', 'p.nav_date')
                ->get()
                ->keyBy('isin');

            $schemes = DB::table('mutual_funds as m')
                ->whereIn('m.isin', $isins)
                ->orderBy('m.scheme_name')
                ->select('m.isin', 'm.scheme_code', 'm.scheme_name', 'm.amc_name', 'm.category', 'm.type', 'm.is_active')
                ->get()
                ->map(function ($row) use ($latestNavs) {
                    $nav          = $latestNavs->get($row->isin);
                    $row->nav     = $nav?->nav;
                    $row->nav_date = $nav?->nav_date;
                    return $row;
                });

            return response()->json([
                'draw'            => intval($request->draw),
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtered,
                'data'            => $schemes->values(),
            ]);
        }

        return view('mutual-funds.index');
    }

    public function show(Request $request, string $isin)
    {
        $scheme = DB::table('mutual_funds as m')
            ->leftJoin(
                DB::raw('(
                    SELECT mfp.isin, mfp.nav, mfp.nav_date
                    FROM mutual_fund_prices mfp
                    INNER JOIN (
                        SELECT isin, MAX(nav_date) as max_date FROM mutual_fund_prices GROUP BY isin
                    ) latest ON mfp.isin = latest.isin AND mfp.nav_date = latest.max_date
                ) as n'),
                'm.isin', '=', 'n.isin'
            )
            ->select('m.*', 'n.nav as latest_nav', 'n.nav_date as latest_nav_date')
            ->where('m.isin', $isin)
            ->first();

        if (!$scheme) {
            abort(404);
        }

        // Calculate returns
        $returns = [];
        if ($scheme->latest_nav_date && $scheme->latest_nav > 0) {
            $periods = [
                '1D'  => now()->subDay(),
                '1M'  => now()->subMonth(),
                '3M'  => now()->subMonths(3),
                '6M'  => now()->subMonths(6),
                '1Y'  => now()->subYear(),
                '3Y'  => now()->subYears(3),
            ];

            $oldest = min(array_map(fn($d) => $d->format('Y-m-d'), $periods));
            $allHistorical = DB::table('mutual_fund_prices')
                ->where('isin', $isin)
                ->where('nav_date', '>=', \Carbon\Carbon::parse($oldest)->subDays(10)->format('Y-m-d'))
                ->where('nav_date', '<', $scheme->latest_nav_date)
                ->orderBy('nav_date')
                ->select('nav_date', 'nav')
                ->get();

            foreach ($periods as $label => $targetCarbon) {
                $best = $allHistorical
                    ->filter(fn($r) => abs(\Carbon\Carbon::parse($r->nav_date)->diffInDays($targetCarbon)) <= 10)
                    ->sortBy(fn($r) => abs(\Carbon\Carbon::parse($r->nav_date)->diffInDays($targetCarbon)))
                    ->first();
                if ($best && $best->nav > 0) {
                    $chg = (($scheme->latest_nav - $best->nav) / $best->nav) * 100;
                    $returns[$label] = [
                        'ref_nav'    => (float)$best->nav,
                        'ref_date'   => $best->nav_date,
                        'return_pct' => round($chg, 4),
                    ];
                }
            }
        }

        if ($request->ajax()) {
            $query    = DB::table('mutual_fund_prices')->where('isin', $isin);
            $total    = $query->count();
            $limit    = (int) ($request->length ?? 50);
            $start    = (int) ($request->start  ?? 0);
            $data     = $query->orderBy('nav_date', 'desc')->skip($start)->take($limit)->get();

            return response()->json([
                'draw'            => intval($request->draw),
                'recordsTotal'    => $total,
                'recordsFiltered' => $total,
                'data'            => $data,
            ]);
        }

        $chartData = DB::table('mutual_fund_prices')
            ->where('isin', $isin)
            ->where('nav_date', '>=', now()->subMonths(12)->format('Y-m-d'))
            ->orderBy('nav_date')
            ->select('nav_date', 'nav')
            ->get();

        return view('mutual-funds.show', compact('scheme', 'returns', 'chartData'));
    }

    public function prices(Request $request)
    {
        if ($request->ajax()) {
            $query = DB::table('mutual_fund_prices as p')
                ->join('mutual_funds as m', 'p.isin', '=', 'm.isin')
                ->select('p.isin', 'p.nav_date', 'p.nav', 'm.scheme_name', 'm.amc_name', 'm.category');

            // Date filters — always apply at least one to avoid full-table scans.
            if ($request->filled('date_from')) {
                $query->where('p.nav_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->where('p.nav_date', '<=', $request->date_to);
            }

            // If neither date is supplied default to the latest available nav_date.
            if (!$request->filled('date_from') && !$request->filled('date_to')) {
                $latestDate = DB::table('mutual_fund_prices')->max('nav_date');
                if ($latestDate) {
                    $query->where('p.nav_date', $latestDate);
                }
            }

            if ($request->filled('isin')) {
                $query->where('p.isin', $request->isin);
            }

            if ($search = $request->input('search.value')) {
                $query->where(function ($q) use ($search) {
                    $q->where('m.scheme_name', 'like', "%{$search}%")
                      ->orWhere('p.isin',       'like', "%{$search}%");
                });
            }

            $filtered = $query->count();
            $total    = DB::table('mutual_fund_prices')->count();
            $limit    = max(1, (int) ($request->length ?? 25));
            $start    = max(0, (int) ($request->start  ?? 0));

            $data = $query->orderBy('p.nav_date', 'desc')->skip($start)->take($limit)->get();

            return response()->json([
                'draw'            => intval($request->draw),
                'recordsTotal'    => $total,
                'recordsFiltered' => $filtered,
                'data'            => $data,
            ]);
        }

        $latestDate = DB::table('mutual_fund_prices')->max('nav_date');

        return view('mutual-funds.prices', compact('latestDate'));
    }
}
