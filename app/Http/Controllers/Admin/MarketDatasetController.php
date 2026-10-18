<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyFundamental;
use App\Models\Equity;
use App\Models\GlobalInstrument;
use App\Models\InstitutionalActivity;
use App\Models\Ipo;
use App\Services\CompanyFundamentalsSyncService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketDatasetController extends Controller
{
    public function insights(Request $request): View
    {
        $ipos = Ipo::query()
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->orderByDesc('bidding_start_date')
            ->paginate(15, ['*'], 'ipo_page')
            ->withQueryString();

        $activity = InstitutionalActivity::query()
            ->where('data_type', 'NSE_EQ|CASH')
            ->where('interval', '1D')
            ->orderByDesc('activity_at')
            ->paginate(20, ['*'], 'activity_page')
            ->withQueryString();

        return view('admin.market-datasets.insights', compact('ipos', 'activity') + [
            'summary' => [
                'ipos' => Ipo::count(),
                'open_ipos' => Ipo::where('status', 'open')->count(),
                'activity_records' => InstitutionalActivity::count(),
                'last_sync' => max(Ipo::max('synced_at') ?? '', InstitutionalActivity::max('synced_at') ?? '') ?: null,
            ],
            'statuses' => Ipo::query()->distinct()->orderBy('status')->pluck('status'),
        ]);
    }

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

    public function showCompanyFundamental(CompanyFundamental $companyFundamental): View
    {
        $companyFundamental->load('equity');

        return view('admin.market-datasets.company-fundamental-show', compact('companyFundamental'));
    }
}
