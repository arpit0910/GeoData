<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Services\CompanyFundamentalsSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

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

    public function companyFundamentals(Request $request): View
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
        ]);
    }

    public function syncCompanyFundamentals(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'isins' => ['nullable', 'string', 'max:5000'],
            'datasets' => ['nullable', 'array'],
            'datasets.*' => ['string', 'in:'.implode(',', CompanyFundamentalsSyncService::DATASETS)],
            'limit' => ['required', 'integer', 'min:1', 'max:1000'],
            'delay' => ['required', 'integer', 'min:0', 'max:5000'],
            'stale_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
        ]);

        $isins = collect(preg_split('/[\s,]+/', $validated['isins'] ?? ''))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))->filter()->unique()->values()->all();
        $arguments = [
            '--limit' => $validated['limit'],
            '--delay' => $validated['delay'],
        ];
        if ($isins) {
            $arguments['--isin'] = $isins;
        }
        if (! empty($validated['datasets'])) {
            $arguments['--dataset'] = $validated['datasets'];
        }
        if ($request->filled('stale_days') && ! $isins) {
            $arguments['--stale-days'] = $validated['stale_days'];
        }

        set_time_limit(0);
        $exitCode = Artisan::call('market:sync-company-fundamentals', $arguments);

        return back()->with($exitCode === 0 ? 'success' : 'error', trim(Artisan::output()));
    }

    public function showCompanyFundamental(CompanyFundamental $companyFundamental): View
    {
        $companyFundamental->load('equity');

        return view('admin.market-datasets.company-fundamental-show', compact('companyFundamental'));
    }
}
