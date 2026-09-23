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
                    ->orWhere('q.symbol', 'like', "%{$search}%")
                    ->orWhere('e.company_name', 'like', "%{$search}%");
            });
        }
        if ($filters['exchange'] ?? null) {
            $query->where('q.exchange', $filters['exchange']);
        }
        if ($filters['freshness'] ?? null) {
            $query->where('q.quoted_at', $filters['freshness'] === 'recent' ? '>=' : '<', $cutoff);
        }
        $quotes = $query->select('q.*', 'e.company_name')->orderByDesc('q.fetched_at')->orderBy('q.id')
            ->paginate(25)->withQueryString();
        foreach ($quotes as $quote) {
            $quote->details = json_decode($quote->payload, true) ?: [];
            $quote->quoted_time = Carbon::parse($quote->quoted_at, 'UTC')->timezone('Asia/Kolkata');
            $quote->fetched_time = Carbon::parse($quote->fetched_at, 'UTC')->timezone('Asia/Kolkata');
            $quote->stale = $quote->quoted_at < $cutoff;
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
