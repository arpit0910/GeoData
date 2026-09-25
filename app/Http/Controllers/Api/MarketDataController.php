<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CorporateAction;
use App\Models\Equity;
use App\Models\MarketNews;
use App\Models\MfMaster;
use App\Models\MfNavHistory;
use App\Services\EquityQuoteService;
use App\Services\MarketDataService;
use App\Services\UpstoxMarketDataService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class MarketDataController extends Controller
{
    public function __construct(
        private readonly MarketDataService $marketDataService,
        private readonly UpstoxMarketDataService $upstoxService,
        private readonly EquityQuoteService $quoteStore
    ) {
    }

    /**
     * GET /api/v1/market/stocks
     * Retrieve all Indian stocks with real-time Upstox quotes and day statistics.
     */
    public function stocks(Request $request): JsonResponse
    {
        // Backward compatibility for explicit US mock symbols request
        if ($request->has('symbols') && !$request->has('page') && !$request->has('instrument_type') && !$request->has('search')) {
            $symbols = $this->marketDataService->normalizeSymbols(
                $request->query('symbols'),
                ['AAPL', 'MSFT', 'TSLA', 'AMZN', 'GOOGL']
            );

            return response()->json([
                'success' => true,
                'type' => 'stocks',
                'symbols' => $symbols,
                'data' => $this->marketDataService->getQuotes($symbols),
            ]);
        }

        $latestPriceSub = DB::table('equity_prices')
            ->select('isin', DB::raw('MAX(id) as latest_price_id'))
            ->groupBy('isin');

        $query = DB::table('equities')
            ->leftJoin('equity_quotes', function ($join) {
                $join->on('equities.isin', '=', 'equity_quotes.isin')
                    ->where('equity_quotes.exchange', '=', 'NSE');
            })
            ->leftJoinSub($latestPriceSub, 'latest_ep', function ($join) {
                $join->on('equities.isin', '=', 'latest_ep.isin');
            })
            ->leftJoin('equity_prices', 'latest_ep.latest_price_id', '=', 'equity_prices.id')
            ->select([
                'equities.id',
                'equities.isin',
                'equities.company_name',
                'equities.nse_symbol',
                'equities.bse_symbol',
                'equities.series',
                'equities.industry',
                'equities.sector',
                'equity_quotes.price as live_price',
                'equity_quotes.quoted_at as live_time',
                DB::raw('COALESCE(equity_prices.nse_prev_close, equity_prices.bse_prev_close) as prev_close'),
                DB::raw('COALESCE(equity_prices.nse_open, equity_prices.bse_open) as day_open'),
                DB::raw('COALESCE(equity_prices.nse_high, equity_prices.bse_high) as day_high'),
                DB::raw('COALESCE(equity_prices.nse_low, equity_prices.bse_low) as day_low'),
                DB::raw('COALESCE(equity_prices.nse_volume, equity_prices.bse_volume) as day_volume'),
                'equity_prices.market_cap',
                'equity_prices.pe_ratio',
            ])
            ->where('equities.is_active', true);

        // Filter by instrument type
        $instrumentType = strtolower(trim((string) $request->input('instrument_type', 'stocks')));
        if ($instrumentType === 'stocks') {
            $query->whereIn('equities.series', ['EQ', 'BE', 'SM', 'BZ']);
        } elseif ($instrumentType === 'bonds') {
            $query->whereNotIn('equities.series', ['EQ', 'BE', 'SM', 'BZ']);
        }

        // Filter by exchange
        $exchange = strtolower(trim((string) $request->input('exchange', 'all')));
        if ($exchange === 'nse') {
            $query->whereNotNull('equities.nse_symbol')->where('equities.nse_symbol', '<>', '');
        } elseif ($exchange === 'bse') {
            $query->whereNotNull('equities.bse_symbol')->where('equities.bse_symbol', '<>', '');
        }

        // Filter by live status
        if ($request->input('filter') === 'live') {
            $query->whereNotNull('equity_quotes.price');
        }

        // Search filter
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

        // Sorting
        $sort = strtolower(trim((string) $request->input('sort', 'volume_desc')));
        match ($sort) {
            'name_asc' => $query->orderBy('equities.company_name', 'asc'),
            'name_desc' => $query->orderBy('equities.company_name', 'desc'),
            'price_desc' => $query->orderByRaw('COALESCE(equity_quotes.price, equity_prices.nse_prev_close, equity_prices.bse_prev_close, 0) DESC'),
            'price_asc' => $query->orderByRaw('COALESCE(equity_quotes.price, equity_prices.nse_prev_close, equity_prices.bse_prev_close, 99999999) ASC'),
            'gainers' => $query->whereNotNull('equity_quotes.price')
                ->whereNotNull('equity_prices.nse_prev_close')
                ->where('equity_prices.nse_prev_close', '>', 0)
                ->orderByRaw('((equity_quotes.price - equity_prices.nse_prev_close) / equity_prices.nse_prev_close) DESC'),
            'losers' => $query->whereNotNull('equity_quotes.price')
                ->whereNotNull('equity_prices.nse_prev_close')
                ->where('equity_prices.nse_prev_close', '>', 0)
                ->orderByRaw('((equity_quotes.price - equity_prices.nse_prev_close) / equity_prices.nse_prev_close) ASC'),
            default => $query->orderByRaw('CASE WHEN equity_quotes.price IS NOT NULL THEN 0 ELSE 1 END')
                ->orderByRaw('COALESCE(equity_prices.nse_volume, equity_prices.bse_volume, 0) DESC')
                ->orderBy('equities.company_name'),
        };

        $perPage = min(max((int) $request->input('per_page', 50), 10), 1000);
        $page = max((int) $request->input('page', 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($stock) {
            $livePrice = $stock->live_price !== null ? (float) $stock->live_price : null;
            $prevClose = $stock->prev_close !== null ? (float) $stock->prev_close : null;
            $change = null;
            $changePercent = null;

            if ($livePrice !== null && $prevClose !== null && $prevClose > 0) {
                $change = round($livePrice - $prevClose, 2);
                $changePercent = round(($change / $prevClose) * 100, 2);
            }

            return [
                'isin' => $stock->isin,
                'company_name' => $stock->company_name,
                'nse_symbol' => $stock->nse_symbol,
                'bse_symbol' => $stock->bse_symbol,
                'symbol' => $stock->nse_symbol ?: $stock->bse_symbol ?: $stock->isin,
                'exchange' => $stock->nse_symbol ? 'NSE' : ($stock->bse_symbol ? 'BSE' : 'EQ'),
                'series' => $stock->series ?: 'EQ',
                'industry' => $stock->industry,
                'sector' => $stock->sector,
                'live_price' => $livePrice,
                'previous_close' => $prevClose,
                'change' => $change,
                'change_percent' => $changePercent,
                'day_open' => $stock->day_open !== null ? (float) $stock->day_open : null,
                'day_high' => $stock->day_high !== null ? (float) $stock->day_high : null,
                'day_low' => $stock->day_low !== null ? (float) $stock->day_low : null,
                'day_volume' => $stock->day_volume !== null ? (int) $stock->day_volume : null,
                'market_cap' => $stock->market_cap,
                'pe_ratio' => $stock->pe_ratio,
                'quoted_at' => $stock->live_time,
                'source' => $livePrice !== null ? 'exchange_live' : 'historical',
            ];
        });

        return response()->json([
            'success' => true,
            'type' => 'stocks',
            'data' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
            ],
            'provider' => 'Real-Time Market Feeds',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/v1/market/mutual-funds
     * Retrieve all AMFI Mutual Fund schemes with NAVs and return metrics.
     */
    public function mutualFunds(Request $request): JsonResponse
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
        match ($sort) {
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

        $perPage = min(max((int) $request->input('per_page', 50), 10), 1000);
        $page = max((int) $request->input('page', 1), 1);
        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'type' => 'mutual_funds',
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem() ?? 0,
                'to' => $paginator->lastItem() ?? 0,
            ],
            'provider' => 'AMFI Official Mutual Fund Feeds',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * GET /api/v1/market/isin/{isin}
     * Retrieve complete real-time market data, quotes, NAV, corporate actions, and news for any particular ISIN.
     */
    public function byIsin(Request $request, string $isin): JsonResponse
    {
        $isin = strtoupper(trim($isin));

        if (!preg_match('/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/', $isin)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid ISIN format: '{$isin}'. ISIN must be 12 alphanumeric characters.",
            ], 422);
        }

        // 1. Check Equities table
        $equity = DB::table('equities')->where('isin', $isin)->first();

        if ($equity) {
            // Check if live on-demand refresh is requested
            if ($request->boolean('live') && !empty($equity->upstox_nse_instrument_key)) {
                try {
                    $quotes = $this->upstoxService->ltp([$equity->upstox_nse_instrument_key]);
                    if (!empty($quotes[$equity->upstox_nse_instrument_key])) {
                        $this->quoteStore->store($isin, 'NSE', $quotes[$equity->upstox_nse_instrument_key]);
                    }
                } catch (Throwable $e) {
                    // Log error and fallback to stored quote
                }
            }

            // Latest Quote
            $quote = DB::table('equity_quotes')
                ->where('isin', $isin)
                ->orderByDesc('quoted_at')
                ->first();

            // Latest Daily Price & Fundamentals
            $price = DB::table('equity_prices')
                ->where('isin', $isin)
                ->orderByDesc('id')
                ->first();

            // Corporate Actions for this ISIN
            $actions = CorporateAction::where('isin', $isin)
                ->orderByDesc('expiry_date')
                ->limit(10)
                ->get();

            // News for this ISIN or Symbol
            $symbol = $equity->nse_symbol ?: $equity->bse_symbol;
            $news = MarketNews::where('isin', $isin)
                ->orWhere(function ($q) use ($symbol) {
                    if ($symbol) {
                        $q->where('symbol', $symbol);
                    }
                })
                ->orderByDesc('published_at')
                ->limit(5)
                ->get();

            $livePrice = $quote ? (float) $quote->price : null;
            $prevClose = $price ? (float) ($price->nse_prev_close ?: $price->bse_prev_close) : null;
            $change = null;
            $changePercent = null;

            if ($livePrice !== null && $prevClose !== null && $prevClose > 0) {
                $change = round($livePrice - $prevClose, 2);
                $changePercent = round(($change / $prevClose) * 100, 2);
            }

            return response()->json([
                'success' => true,
                'type' => 'equity',
                'isin' => $isin,
                'data' => [
                    'company' => [
                        'name' => $equity->company_name,
                        'nse_symbol' => $equity->nse_symbol,
                        'bse_symbol' => $equity->bse_symbol,
                        'series' => $equity->series,
                        'industry' => $equity->industry,
                        'sector' => $equity->sector,
                        'basic_industry' => $equity->basic_industry,
                        'instrument_key' => $equity->upstox_nse_instrument_key,
                    ],
                    'live_quote' => [
                        'price' => $livePrice,
                        'previous_close' => $prevClose,
                        'change' => $change,
                        'change_percent' => $changePercent,
                        'day_open' => $price ? (float) ($price->nse_open ?: $price->bse_open) : null,
                        'day_high' => $price ? (float) ($price->nse_high ?: $price->bse_high) : null,
                        'day_low' => $price ? (float) ($price->nse_low ?: $price->bse_low) : null,
                        'day_volume' => $price ? (int) ($price->nse_volume ?: $price->bse_volume) : null,
                        'quoted_at' => $quote ? $quote->quoted_at : null,
                        'source' => $quote ? 'exchange_live' : 'database',
                    ],
                    'fundamentals' => [
                        'market_cap' => $price ? $price->market_cap : null,
                        'pe_ratio' => $price ? $price->pe_ratio : null,
                    ],
                    'corporate_actions' => $actions,
                    'news' => $news,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        // 2. Check Mutual Funds table
        $mf = DB::table('mutual_funds')->where('isin', $isin)->first();

        if ($mf) {
            $mfPrice = DB::table('mutual_fund_prices')
                ->where('isin', $isin)
                ->orderByDesc('id')
                ->first();

            $history = DB::table('mutual_fund_prices')
                ->where('isin', $isin)
                ->orderByDesc('nav_date')
                ->limit(30)
                ->get();

            return response()->json([
                'success' => true,
                'type' => 'mutual_fund',
                'isin' => $isin,
                'data' => [
                    'scheme' => [
                        'code' => $mf->scheme_code,
                        'name' => $mf->scheme_name,
                        'amc' => $mf->amc_name,
                        'category' => $mf->category,
                        'sub_category' => $mf->sub_category,
                        'type' => $mf->type,
                    ],
                    'nav' => [
                        'current' => $mfPrice ? (float) $mfPrice->nav : null,
                        'date' => $mfPrice ? $mfPrice->nav_date : null,
                        'chg_1d' => $mfPrice ? (float) $mfPrice->chg_1d : null,
                        'chg_1m' => $mfPrice ? (float) $mfPrice->chg_1m : null,
                        'chg_1y' => $mfPrice ? (float) $mfPrice->chg_1y : null,
                        'chg_3y' => $mfPrice ? (float) $mfPrice->chg_3y : null,
                    ],
                    'nav_history' => $history,
                ],
                'timestamp' => now()->toIso8601String(),
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => "No equity stock or mutual fund scheme found matching ISIN '{$isin}'.",
        ], 404);
    }

    /**
     * Backward compatibility endpoint for /api/v1/market/equity/{isin}
     */
    public function equityQuote(Request $request, string $isin): JsonResponse
    {
        return $this->byIsin($request, $isin);
    }

    /**
     * POST /api/v1/market/sync
     * Trigger an on-demand real-time sync with Upstox for requested ISINs or top active stocks.
     */
    public function sync(Request $request): JsonResponse
    {
        $isins = collect($request->input('isins', []))
            ->map(fn ($i) => strtoupper(trim((string) $i)))
            ->filter()
            ->unique()
            ->values();

        $query = DB::table('equities')
            ->where('is_active', true)
            ->whereNotNull('upstox_nse_instrument_key')
            ->where('upstox_nse_instrument_key', '<>', '');

        if ($isins->isNotEmpty()) {
            $query->whereIn('isin', $isins->all());
        } else {
            // Default sync: sync top 30 active stocks
            $limit = min(max((int) $request->input('limit', 30), 1), 100);
            $query->whereIn('series', ['EQ', 'BE', 'SM', 'BZ'])->limit($limit);
        }

        $equities = $query->get(['isin', 'upstox_nse_instrument_key']);

        if ($equities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active equities found matching requested ISINs for synchronization.',
            ], 404);
        }

        $keys = $equities->pluck('upstox_nse_instrument_key')->all();

        try {
            $quotes = $this->upstoxService->ltp($keys);
            $syncedCount = 0;

            foreach ($equities as $eq) {
                $key = $eq->upstox_nse_instrument_key;
                if (!empty($quotes[$key])) {
                    $saved = $this->quoteStore->store($eq->isin, 'NSE', $quotes[$key]);
                    if ($saved) {
                        $syncedCount++;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Successfully synchronized {$syncedCount} stock quotes from real-time market feed.",
                'requested' => count($keys),
                'synced' => $syncedCount,
                'timestamp' => now()->toIso8601String(),
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Market data synchronization error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/v1/market/indices
     */
    public function indices(Request $request): JsonResponse
    {
        $symbols = $this->marketDataService->normalizeSymbols(
            $request->query('symbols'),
            ['^GSPC', '^DJI', '^IXIC']
        );

        return response()->json([
            'success' => true,
            'type' => 'indices',
            'symbols' => $symbols,
            'data' => $this->marketDataService->getQuotes($symbols),
        ]);
    }

    /**
     * GET /api/v1/market/quote/{symbol}
     */
    public function quote(string $symbol): JsonResponse
    {
        $symbol = trim($symbol);

        return response()->json([
            'success' => true,
            'symbol' => strtoupper($symbol),
            'data' => $this->marketDataService->getQuote($symbol),
        ]);
    }

    /**
     * GET /api/v1/market/news
     */
    public function news(Request $request): JsonResponse
    {
        $query = MarketNews::query();

        if ($request->filled('isin')) {
            $query->where('isin', strtoupper(trim((string) $request->input('isin'))));
        }

        if ($request->filled('symbol')) {
            $query->where('symbol', strtoupper(trim((string) $request->input('symbol'))));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('summary', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%");
                });
            }
        }

        $news = $query->orderByDesc('published_at')->orderByDesc('id')->paginate(
            min(max((int) $request->input('per_page', 20), 5), 100)
        );

        return response()->json([
            'success' => true,
            'data' => $news->items(),
            'pagination' => [
                'current_page' => $news->currentPage(),
                'last_page' => $news->lastPage(),
                'per_page' => $news->perPage(),
                'total' => $news->total(),
            ],
            'provider' => 'Financial News Wire',
        ]);
    }

    /**
     * GET /api/v1/market/corporate-actions
     */
    public function corporateActions(Request $request): JsonResponse
    {
        $query = CorporateAction::query();

        if ($request->filled('isin')) {
            $query->where('isin', strtoupper(trim((string) $request->input('isin'))));
        }

        if ($request->filled('type') && strtoupper((string) $request->input('type')) !== 'ALL') {
            $query->where('type', strtoupper(trim((string) $request->input('type'))));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('symbol', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%");
                });
            }
        }

        $actions = $query->orderByDesc('expiry_date')->orderByDesc('id')->paginate(
            min(max((int) $request->input('per_page', 20), 5), 100)
        );

        return response()->json([
            'success' => true,
            'data' => $actions->items(),
            'pagination' => [
                'current_page' => $actions->currentPage(),
                'last_page' => $actions->lastPage(),
                'per_page' => $actions->perPage(),
                'total' => $actions->total(),
            ],
            'provider' => 'Exchange Corporate Actions',
        ]);
    }

    public function splits(Request $request): JsonResponse
    {
        $request->merge(['type' => 'SPLIT']);
        return $this->corporateActions($request);
    }

    public function bonuses(Request $request): JsonResponse
    {
        $request->merge(['type' => 'BONUS']);
        return $this->corporateActions($request);
    }

    public function dividends(Request $request): JsonResponse
    {
        $request->merge(['type' => 'DIVIDEND']);
        return $this->corporateActions($request);
    }
}
