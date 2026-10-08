<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CorporateAction;
use App\Models\Equity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class CorporateActionController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'type' => 'nullable|in:DIVIDEND,SPLIT,BONUS,RIGHTS,EVENT',
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
        ]);

        $actions = CorporateAction::query()
            ->when(trim((string) ($filters['search'] ?? '')) !== '', function ($query) use ($filters) {
                $search = trim((string) $filters['search']);
                $query->where(fn ($nested) => $nested
                    ->where('company_name', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%")
                    ->orWhere('isin', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"));
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('expiry_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('expiry_date', '<=', $date))
            ->orderByDesc('expiry_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $eligible = Equity::query()->where('is_active', true)->whereNotNull('isin')->where('isin', '<>', '');
        $summary = [
            'events' => CorporateAction::count(),
            'companies_with_events' => CorporateAction::distinct()->count('isin'),
            'eligible_companies' => (clone $eligible)->count(),
            'synced_companies' => (clone $eligible)->whereNotNull('corporate_actions_synced_at')->count(),
            'failed_companies' => (clone $eligible)->whereNotNull('corporate_actions_sync_error')->count(),
            'last_success' => (clone $eligible)->max('corporate_actions_synced_at'),
            'last_attempt' => (clone $eligible)->max('corporate_actions_sync_attempted_at'),
        ];
        $summary['pending_companies'] = max(0, $summary['eligible_companies'] - $summary['synced_companies']);

        $failedCompanies = (clone $eligible)
            ->whereNotNull('corporate_actions_sync_error')
            ->orderByDesc('corporate_actions_sync_attempted_at')
            ->limit(10)
            ->get([
                'id', 'isin', 'company_name', 'nse_symbol', 'bse_symbol',
                'corporate_actions_sync_attempted_at', 'corporate_actions_sync_error',
            ]);

        return view('admin.corporate-actions.index', compact('actions', 'summary', 'failedCompanies', 'filters'));
    }

    public function show(CorporateAction $corporateAction): View
    {
        $corporateAction->load('equity');

        return view('admin.corporate-actions.show', compact('corporateAction'));
    }

    public function sync(Request $request): RedirectResponse
    {
        $input = $request->validate([
            'isin' => ['nullable', 'string', 'max:20', 'regex:/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/i'],
            'limit' => 'nullable|integer|in:25,50,100',
        ]);
        $isin = strtoupper(trim((string) ($input['isin'] ?? '')));
        $arguments = ['--delay' => 200];
        if ($isin !== '') {
            $arguments['--isin'] = [$isin];
            $arguments['--delay'] = 0;
        } else {
            $arguments['--limit'] = (int) ($input['limit'] ?? 25);
        }

        set_time_limit(0);

        try {
            $exitCode = Artisan::call('market:sync-upstox-events', $arguments);
            $output = trim(Artisan::output());
        } catch (\Throwable $exception) {
            return back()->withInput()->with('error', 'Corporate-action sync could not start: '.$exception->getMessage());
        }

        return redirect()->route('admin.corporate-actions.index')->with(
            $exitCode === 0 ? 'success' : 'error',
            $output !== '' ? $output : 'The corporate-action sync returned no output.'
        );
    }
}
