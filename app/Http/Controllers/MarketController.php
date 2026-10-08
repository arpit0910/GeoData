<?php

namespace App\Http\Controllers;

use App\Models\CorporateAction;
use App\Models\CompanyFundamental;
use App\Models\MarketNews;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketController extends Controller
{
    /**
     * Display the live market page.
     */
    public function index(Request $request)
    {
        $marketStatus = $this->getMarketStatus();
        $stats = $this->getSummaryStats();
        $latestNews = MarketNews::published()
            ->select(['id', 'title', 'summary', 'symbol', 'thumbnail', 'article_url', 'published_at'])
            ->orderByDesc('published_at')->orderByDesc('id')->limit(3)->get();
        $upcomingActions = CorporateAction::select(['id', 'symbol', 'company_name', 'type', 'name', 'expiry_date'])
            ->where(function ($query) {
                $query->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->toDateString());
            })->orderBy('expiry_date')->limit(5)->get();

        return view('website.market', compact('marketStatus', 'stats', 'latestNews', 'upcomingActions'));
    }

    public function stocks(Request $request)
    {
        $stocks = $this->getStocksQuery($request)->paginate(25)->withQueryString();
        return view('website.market-stocks', ['stocks' => $stocks, 'marketStatus' => $this->getMarketStatus()]);
    }

    public function mutualFunds(Request $request)
    {
        $funds = $this->getMfQuery($request)->paginate(24)->withQueryString();
        $categories = DB::table('mutual_funds')->whereNotNull('category')->where('category', '<>', '')
            ->distinct()->orderBy('category')->pluck('category');

        return view('website.market-mutual-funds', compact('funds', 'categories'));
    }

    public function news(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $news = MarketNews::published()
            ->select(['id', 'isin', 'symbol', 'title', 'summary', 'thumbnail', 'article_url', 'published_at'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                });
            })->orderByDesc('published_at')->orderByDesc('id')->paginate(12)->withQueryString();

        return view('website.market-news', compact('news'));
    }

    public function fundamentals(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $dataset = trim((string) $request->input('dataset'));
        $fundamentals = CompanyFundamental::query()
            ->with('equity:id,isin,company_name,nse_symbol,bse_symbol')
            ->select(['id', 'equity_id', 'isin', 'dataset', 'statement_type', 'time_period', 'synced_at'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('isin', 'like', "%{$search}%")
                        ->orWhereHas('equity', function ($equity) use ($search) {
                            $equity->where('company_name', 'like', "%{$search}%")
                                ->orWhere('nse_symbol', 'like', "%{$search}%")
                                ->orWhere('bse_symbol', 'like', "%{$search}%");
                        });
                });
            })->when($dataset !== '', fn ($query) => $query->where('dataset', $dataset))
            ->orderByDesc('synced_at')->paginate(24)->withQueryString();
        $datasets = CompanyFundamental::distinct()->orderBy('dataset')->pluck('dataset');

        return view('website.market-fundamentals', compact('fundamentals', 'datasets'));
    }

    public function fundamental(CompanyFundamental $companyFundamental)
    {
        $companyFundamental->load('equity:id,isin,company_name,nse_symbol,bse_symbol');
        return view('website.market-fundamental-show', compact('companyFundamental'));
    }

    public function corporateActions(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $type = strtoupper(trim((string) $request->input('type')));
        $actions = CorporateAction::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('company_name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('isin', 'like', "%{$search}%");
                });
            })->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderByDesc('expiry_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('website.market-actions', compact('actions'));
    }

    /**
     * AJAX endpoint for live updating, searching, and filtering.
     */
    public function data(Request $request): JsonResponse
    {
        $section = $request->input('section', 'stocks');
        $page = max((int) $request->input('page', 1), 1);

        // On-demand live Upstox sync during auto-sync or manual refresh
        if ($request->boolean('sync') && $section === 'stocks') {
            try {
                $instrumentType = strtolower(trim((string) $request->input('instrument_type', 'stocks')));
                $perPage = min(max((int) $request->input('per_page', 25), 10), 100);
                $query = $this->getStocksQuery($request)->forPage($page, $perPage);

                // For bonds/fixed income whose prices do not change during the day, sync once daily only
                if ($instrumentType === 'bonds') {
                    $todayStartUtc = now('Asia/Kolkata')->startOfDay()->utc();
                    $syncedTodayIsins = DB::table('equity_quotes')
                        ->where('fetched_at', '>=', $todayStartUtc)
                        ->pluck('isin')
                        ->all();
                    if (!empty($syncedTodayIsins)) {
                        $query->whereNotIn('equities.isin', $syncedTodayIsins);
                    }
                }

                $targets = $query
                    ->pluck('upstox_nse_instrument_key', 'isin')
                    ->filter()
                    ->all();
                if (!empty($targets)) {
                    $keys = array_values($targets);
                    $upstox = app(\App\Services\UpstoxMarketDataService::class);
                    $quotes = $upstox->ltp($keys);
                    $store = app(\App\Services\EquityQuoteService::class);
                    foreach ($targets as $isin => $key) {
                        if (!empty($quotes[$key])) {
                            $store->store($isin, 'NSE', $quotes[$key]);
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Gracefully fallback to cached data if Upstox is unreachable
            }
        }

        return match ($section) {
            'stocks' => response()->json([
                'success' => true,
                'data' => $this->getStocksQuery($request)->paginate(
                    min(max((int) $request->input('per_page', 25), 10), 100),
                    ['*'],
                    'page',
                    $page
                ),
                'market_status' => $this->getMarketStatus(),
                'last_sync' => now()->setTimezone('Asia/Kolkata')->format('h:i:s A'),
            ]),
            'mf' => response()->json([
                'success' => true,
                'data' => $this->getMfQuery($request)->paginate(
                    min(max((int) $request->input('per_page', 24), 12), 96),
                    ['*'],
                    'page',
                    $page
                ),
            ]),
            'news' => response()->json([
                'success' => true,
                'data' => MarketNews::published()->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim((string) $request->input('search'));
                    if ($search !== '') {
                        $q->where(function ($sq) use ($search) {
                            $sq->where('title', 'like', "%{$search}%")
                                ->orWhere('summary', 'like', "%{$search}%")
                                ->orWhere('symbol', 'like', "%{$search}%")
                                ->orWhere('isin', 'like', "%{$search}%");
                        });
                    }
                })->orderByDesc('published_at')->orderByDesc('id')->paginate(
                    min(max((int) $request->input('per_page', 10), 5), 50),
                    ['*'],
                    'page',
                    $page
                ),
            ]),
            'events' => response()->json([
                'success' => true,
                'data' => CorporateAction::when($request->filled('type') && strtoupper($request->input('type')) !== 'ALL', function ($q) use ($request) {
                    $q->where('type', strtoupper(trim($request->input('type'))));
                })->when($request->filled('search'), function ($q) use ($request) {
                    $search = trim((string) $request->input('search'));
                    if ($search !== '') {
                        $q->where(function ($sq) use ($search) {
                            $sq->where('company_name', 'like', "%{$search}%")
                                ->orWhere('symbol', 'like', "%{$search}%")
                                ->orWhere('name', 'like', "%{$search}%")
                                ->orWhere('details', 'like', "%{$search}%")
                                ->orWhere('isin', 'like', "%{$search}%");
                        });
                    }
                })->orderByDesc('expiry_date')->orderByDesc('id')->paginate(
                    min(max((int) $request->input('per_page', 12), 5), 50),
                    ['*'],
                    'page',
                    $page
                ),
            ]),
            'overview' => response()->json([
                'success' => true,
                'market_status' => $this->getMarketStatus(),
                'stats' => $this->getSummaryStats(),
            ]),
            default => response()->json(['success' => false, 'message' => 'Unknown section'], 400),
        };
    }

    private function getStocksQuery(Request $request)
    {
        $latestPriceSub = DB::table('equity_prices')
            ->select('isin', DB::raw('MAX(id) as latest_price_id'))
            ->groupBy('isin');

        $query = DB::table('equities')
            ->leftJoin('equity_quotes', 'equities.isin', '=', 'equity_quotes.isin')
            ->leftJoinSub($latestPriceSub, 'latest_ep', function ($join) {
                $join->on('equities.isin', '=', 'latest_ep.isin');
            })
            ->leftJoin('equity_prices', 'latest_ep.latest_price_id', '=', 'equity_prices.id')
            ->select([
                'equities.id',
                'equities.isin',
                'equities.company_name',
                'equities.short_name',
                'equities.nse_symbol',
                'equities.bse_symbol',
                'equities.series',
                'equities.security_type',
                'equities.industry',
                'equities.sector',
                'equity_quotes.price as live_price',
                'equity_quotes.quoted_at as live_time',
                'equity_quotes.previous_close as live_previous_close',
                'equity_quotes.change_percent as live_change_percent',
                'equity_quotes.live_volume',
                'equity_prices.traded_date as price_date',
                DB::raw('COALESCE(equity_prices.nse_close, equity_prices.bse_close) as latest_close'),
                DB::raw('COALESCE(equity_prices.nse_prev_close, equity_prices.bse_prev_close) as prev_close'),
                DB::raw('COALESCE(equity_prices.nse_open, equity_prices.bse_open) as day_open'),
                DB::raw('COALESCE(equity_prices.nse_high, equity_prices.bse_high) as day_high'),
                DB::raw('COALESCE(equity_prices.nse_low, equity_prices.bse_low) as day_low'),
                DB::raw('COALESCE(equity_prices.nse_volume, equity_prices.bse_volume) as day_volume'),
                'equity_prices.market_cap',
                'equity_prices.pe_ratio',
            ])
            ->where('equities.is_active', true);

        // 1. Instrument Type Filter: Default is 'stocks' (True Equities: EQ, BE, SM, BZ)
        $instrumentType = strtolower(trim((string) $request->input('instrument_type', 'stocks')));
        if ($instrumentType === 'stocks') {
            $query->whereIn('equities.series', ['EQ', 'BE', 'SM', 'BZ']);
        } elseif ($instrumentType === 'bonds') {
            $query->whereNotIn('equities.series', ['EQ', 'BE', 'SM', 'BZ']);
        }
        // If 'all', include both stocks and bonds

        // 2. Search Filter
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('equities.company_name', 'like', "%{$search}%")
                        ->orWhere('equities.nse_symbol', 'like', "%{$search}%")
                        ->orWhere('equities.bse_symbol', 'like', "%{$search}%")
                        ->orWhere('equities.isin', 'like', "%{$search}%");
                });
            }
        }

        // 3. Exchange / Live Status Filter
        $filter = strtolower(trim((string) $request->input('filter', 'all')));
        if ($filter === 'live') {
            $query->whereNotNull('equity_quotes.price');
        } elseif ($filter === 'nse') {
            $query->whereNotNull('equities.nse_symbol')->where('equities.nse_symbol', '<>', '');
        } elseif ($filter === 'bse') {
            $query->whereNotNull('equities.bse_symbol')->where('equities.bse_symbol', '<>', '');
        }

        // 4. Sorting
        $sort = strtolower(trim((string) $request->input('sort', 'volume_desc')));
        return match ($sort) {
            'name_asc' => $query->orderBy('equities.company_name', 'asc'),
            'name_desc' => $query->orderBy('equities.company_name', 'desc'),
            'price_desc' => $query->orderByRaw('COALESCE(equity_quotes.price, equity_prices.nse_prev_close, equity_prices.bse_prev_close, 0) DESC'),
            'price_asc' => $query->orderByRaw('COALESCE(equity_quotes.price, equity_prices.nse_prev_close, equity_prices.bse_prev_close, 99999999) ASC'),
            'gainers' => $query->whereNotNull('equity_quotes.price')
                ->whereNotNull('equity_quotes.change_percent')
                ->orderByDesc('equity_quotes.change_percent'),
            'losers' => $query->whereNotNull('equity_quotes.price')
                ->whereNotNull('equity_quotes.change_percent')
                ->orderBy('equity_quotes.change_percent'),
            default => $query->orderByRaw('CASE WHEN equity_quotes.price IS NOT NULL THEN 0 ELSE 1 END')
                ->orderByRaw('COALESCE(equity_prices.nse_volume, equity_prices.bse_volume, 0) DESC')
                ->orderBy('equities.company_name'),
        };
    }

    private function getMfQuery(Request $request)
    {
        $latestMfPriceSub = DB::table('mutual_fund_prices')
            ->select('isin', DB::raw('MAX(id) as latest_mf_price_id'))
            ->groupBy('isin');

        $query = DB::table('mutual_funds')
            ->leftJoinSub($latestMfPriceSub, 'latest_mfp', function ($join) {
                $join->on('mutual_funds.isin', '=', 'latest_mfp.isin');
            })
            ->leftJoin('mutual_fund_prices', 'latest_mfp.latest_mf_price_id', '=', 'mutual_fund_prices.id')
            ->select([
                'mutual_funds.isin',
                'mutual_funds.scheme_code',
                'mutual_funds.scheme_name',
                'mutual_funds.amc_name',
                'mutual_funds.category',
                'mutual_funds.sub_category',
                'mutual_funds.type',
                'mutual_fund_prices.nav',
                'mutual_fund_prices.nav_date',
                'mutual_fund_prices.chg_1d',
                'mutual_fund_prices.chg_1m',
                'mutual_fund_prices.chg_1y',
                'mutual_fund_prices.chg_3y',
            ])
            ->where('mutual_funds.is_active', true);

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('mutual_funds.scheme_name', 'like', "%{$search}%")
                        ->orWhere('mutual_funds.amc_name', 'like', "%{$search}%")
                        ->orWhere('mutual_funds.isin', 'like', "%{$search}%")
                        ->orWhere('mutual_funds.scheme_code', 'like', "%{$search}%");
                });
            }
        }

        if ($request->filled('category') && strtolower($request->input('category')) !== 'all') {
            $query->where('mutual_funds.category', $request->input('category'));
        }

        if ($request->filled('amc') && strtolower($request->input('amc')) !== 'all') {
            $query->where('mutual_funds.amc_name', $request->input('amc'));
        }

        $sort = strtolower(trim((string) $request->input('sort', 'popular')));
        return match ($sort) {
            'name_asc' => $query->orderBy('mutual_funds.scheme_name', 'asc'),
            'name_desc' => $query->orderBy('mutual_funds.scheme_name', 'desc'),
            'nav_desc' => $query->orderByRaw('CASE WHEN mutual_fund_prices.nav IS NOT NULL THEN 0 ELSE 1 END')->orderByDesc('mutual_fund_prices.nav'),
            'nav_asc' => $query->orderByRaw('CASE WHEN mutual_fund_prices.nav IS NOT NULL THEN 0 ELSE 1 END')->orderBy('mutual_fund_prices.nav', 'asc'),
            'chg_1d_desc' => $query->orderByRaw('CASE WHEN mutual_fund_prices.chg_1d IS NOT NULL THEN 0 ELSE 1 END')->orderByDesc('mutual_fund_prices.chg_1d'),
            'chg_1y_desc' => $query->orderByRaw('CASE WHEN mutual_fund_prices.chg_1y IS NOT NULL THEN 0 ELSE 1 END')->orderByDesc('mutual_fund_prices.chg_1y'),
            'chg_3y_desc' => $query->orderByRaw('CASE WHEN mutual_fund_prices.chg_3y IS NOT NULL THEN 0 ELSE 1 END')->orderByDesc('mutual_fund_prices.chg_3y'),
            default => $query->orderByRaw("CASE WHEN mutual_funds.category = 'Equity' THEN 1 WHEN mutual_funds.category IN ('Index', 'ETF') THEN 2 WHEN mutual_funds.category = 'Hybrid' THEN 3 ELSE 4 END")
                ->orderByRaw("CASE WHEN mutual_funds.amc_name IN ('HDFC Mutual Fund', 'SBI Mutual Fund', 'ICICI Prudential Mutual Fund', 'Nippon India Mutual Fund', 'Kotak Mahindra Mutual Fund', 'Axis Mutual Fund') THEN 0 ELSE 1 END")
                ->orderByRaw('CASE WHEN mutual_fund_prices.nav IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('mutual_funds.scheme_name'),
        };
    }

    private function getMarketStatus(): array
    {
        $now = Carbon::now('Asia/Kolkata');
        $dayOfWeek = (int) $now->format('w'); // 0 = Sun, 6 = Sat
        $isWeekday = $dayOfWeek >= 1 && $dayOfWeek <= 5;

        $currentTime = $now->format('H:i');
        $isOpen = $isWeekday && $currentTime >= '09:15' && $currentTime <= '15:30';

        return [
            'is_open' => $isOpen,
            'status' => $isOpen ? 'MARKET OPEN' : 'MARKET CLOSED',
            'ist_time' => $now->format('d M Y, h:i:s A') . ' IST',
            'trading_hours' => '09:15 AM - 03:30 PM IST (Mon - Fri)',
            'next_session' => $isOpen ? 'Closing at 03:30 PM IST' : 'Next trading session at 09:15 AM IST',
        ];
    }

    private function getSummaryStats(): array
    {
        $totalStocks = DB::table('equities')->where('is_active', true)->whereIn('series', ['EQ', 'BE', 'SM', 'BZ'])->count();
        $totalQuotes = DB::table('equity_quotes')->count();
        $totalMfs = DB::table('mutual_funds')->count();
        $totalNews = MarketNews::published()->count();
        $totalCorporateActions = DB::table('corporate_actions')->count();

        $lastQuote = DB::table('equity_quotes')->orderByDesc('quoted_at')->first();

        return [
            'total_stocks' => $totalStocks,
            'live_quotes' => $totalQuotes,
            'total_mfs' => $totalMfs,
            'total_news' => $totalNews,
            'total_corporate_actions' => $totalCorporateActions,
            'last_sync_time' => $lastQuote ? Carbon::parse($lastQuote->quoted_at)->setTimezone('Asia/Kolkata')->format('d M, h:i A') : 'Live Now',
            'provider' => 'SetuGeo Market Data',
        ];
    }
}
