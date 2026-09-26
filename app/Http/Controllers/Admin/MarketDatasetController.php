<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Services\CompanyFundamentalsSyncService;
use App\Services\UpstoxTokenManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class MarketDatasetController extends Controller
{
    public function globalInstruments(Request $request): View
    {
        $query = GlobalInstrument::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('trading_symbol', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%"));
        }
        if ($request->filled('segment')) {
            $query->where('segment', $request->segment);
        }
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $instruments = $query->orderBy('name')->paginate(25)->withQueryString();
        $summary = [
            'total' => GlobalInstrument::count(),
            'active' => GlobalInstrument::where('is_active', true)->count(),
            'countries' => GlobalInstrument::whereNotNull('country')->distinct()->count('country'),
            'last_sync' => GlobalInstrument::max('synced_at'),
        ];
        $segments = GlobalInstrument::query()->distinct()->orderBy('segment')->pluck('segment');

        return view('admin.market-datasets.global-instruments', compact('instruments', 'summary', 'segments'));
    }

    public function syncGlobalInstruments(): RedirectResponse
    {
        $exitCode = Artisan::call('market:sync-global-instruments');

        return back()->with($exitCode === 0 ? 'success' : 'error', trim(Artisan::output()));
    }

    public function companyFundamentals(Request $request, UpstoxTokenManager $tokens): View
    {
        $query = CompanyFundamental::query()->with('equity');

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($query) use ($search) {
                $query->where('isin', 'like', "%{$search}%")
                    ->orWhereHas('equity', fn ($equity) => $equity
                        ->where('company_name', 'like', "%{$search}%")
                        ->orWhere('nse_symbol', 'like', "%{$search}%")
                        ->orWhere('bse_symbol', 'like', "%{$search}%"));
            });
        }
        foreach (['dataset', 'statement_type', 'time_period'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, $request->{$filter});
            }
        }

        $fundamentals = $query->orderByDesc('synced_at')->paginate(25)->withQueryString();
        $eligibleCompanies = Equity::query()
            ->where('is_active', true)
            ->whereIn('series', ['EQ', 'BE', 'SM', 'BZ'])
            ->whereNotNull('isin')->where('isin', '<>', '')->count();
        $syncedCompanies = CompanyFundamental::distinct()->count('isin');
        $summary = [
            'records' => CompanyFundamental::count(),
            'companies' => $syncedCompanies,
            'pending' => max(0, $eligibleCompanies - $syncedCompanies),
            'last_sync' => CompanyFundamental::max('synced_at'),
        ];

        return view('admin.market-datasets.company-fundamentals', [
            'fundamentals' => $fundamentals,
            'summary' => $summary,
            'datasets' => CompanyFundamentalsSyncService::DATASETS,
            'statementTypes' => CompanyFundamental::distinct()->orderBy('statement_type')->pluck('statement_type'),
            'timePeriods' => CompanyFundamental::distinct()->orderBy('time_period')->pluck('time_period'),
            'upstoxToken' => $tokens->current(),
            'upstoxTokenConfigured' => trim((string) config('market_data.upstox.client_id')) !== ''
                && trim((string) config('market_data.upstox.client_secret')) !== '',
            'upstoxNotifierUrl' => route(
                'api.market-data.upstox-token',
                ['secret' => config('market_data.upstox.notifier_secret')]
            ),
        ]);
    }

    public function requestUpstoxToken(UpstoxTokenManager $tokens): RedirectResponse
    {
        try {
            $result = $tokens->requestRenewal();
            $message = $result['requested']
                ? 'A new Upstox token was requested. Approve the request in Upstox; it will be stored automatically.'
                : 'An Upstox token request is already awaiting approval.';

            return back()->with('success', $message);
        } catch (Throwable $exception) {
            report($exception);
            return back()->with('error', $exception->getMessage());
        }
    }

    public function showCompanyFundamental(CompanyFundamental $companyFundamental): View
    {
        $companyFundamental->load('equity');

        return view('admin.market-datasets.company-fundamental-show', compact('companyFundamental'));
    }
}
