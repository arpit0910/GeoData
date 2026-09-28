<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class EquityQuoteController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:100',
            'exchange' => 'nullable|in:NSE,BSE',
            'freshness' => 'nullable|in:recent,stale',
        ]);
        $cutoff = now()->utc()->subSeconds(900)->format('Y-m-d H:i:s');
        $query = DB::table('equity_quotes as q')->leftJoin('equities as e', 'e.isin', '=', 'q.isin');
        if ($search = trim($filters['search'] ?? '')) {
            $query->where(function ($query) use ($search) {
                $query->where('q.isin', 'like', "%{$search}%")
                    ->orWhere('q.nse_symbol', 'like', "%{$search}%")
                    ->orWhere('q.bse_symbol', 'like', "%{$search}%")
                    ->orWhere('e.company_name', 'like', "%{$search}%");
            });
        }
        if ($filters['exchange'] ?? null) {
            $query->whereNotNull('q.'.strtolower($filters['exchange']).'_price');
        }
        if ($filters['freshness'] ?? null) {
            $fields = ($filters['exchange'] ?? null)
                ? ['q.'.strtolower($filters['exchange']).'_quoted_at']
                : ['q.nse_quoted_at', 'q.bse_quoted_at'];

            $query->where(function ($query) use ($fields, $filters, $cutoff) {
                foreach ($fields as $field) {
                    if ($filters['freshness'] === 'recent') {
                        $query->orWhere($field, '>=', $cutoff);
                    } else {
                        $query->where(function ($query) use ($field, $cutoff) {
                            $query->whereNull($field)->orWhere($field, '<', $cutoff);
                        });
                    }
                }
            });
        }
        $quotes = $query->select('q.*', 'e.company_name')->orderByDesc('q.fetched_at')->orderBy('q.id')
            ->paginate(25)->withQueryString();
        foreach ($quotes as $quote) {
            foreach (['nse', 'bse'] as $prefix) {
                $payload = $quote->{$prefix.'_payload'};
                $quote->{$prefix.'_details'} = $payload ? (json_decode($payload, true) ?: []) : [];
                $quote->{$prefix.'_quoted_time'} = $quote->{$prefix.'_quoted_at'}
                    ? Carbon::parse($quote->{$prefix.'_quoted_at'}, 'UTC')->timezone('Asia/Kolkata')
                    : null;
                $quote->{$prefix.'_fetched_time'} = $quote->{$prefix.'_fetched_at'}
                    ? Carbon::parse($quote->{$prefix.'_fetched_at'}, 'UTC')->timezone('Asia/Kolkata')
                    : null;
                $quote->{$prefix.'_stale'} = !$quote->{$prefix.'_quoted_at'} || $quote->{$prefix.'_quoted_at'} < $cutoff;
            }
        }
        return view('equities.quotes', compact('quotes', 'filters'));
    }

    public function sync()
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        try {
            $exitCode = Artisan::call('market:sync-upstox-quotes', ['--batch-size' => 500]);
            $output = trim(Artisan::output());
        } catch (\Throwable $exception) {
            return redirect()->route('equities.quotes')->with(
                'error',
                'Quote sync could not start: '.$exception->getMessage()
            );
        }
        $summary = last(array_filter(explode("\n", $output))) ?: '';

        return redirect()->route('equities.quotes')->with(
            $exitCode === 0 ? 'success' : 'error',
            $exitCode === 0
                ? 'Latest stock quotes synced. '.$summary
                : 'Quote sync failed. '.($output ?: 'No eligible quotes were returned by the provider.')
        );
    }
}
