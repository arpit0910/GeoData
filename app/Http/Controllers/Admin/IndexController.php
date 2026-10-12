<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Index;
use App\Models\IndexPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\UpstoxInstrumentSyncService;

class IndexController extends Controller
{
    /**
     * Display a listing of indices.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Index::query();

            // Handle Search
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('index_code', 'like', "%{$search}%")
                      ->orWhere('index_name', 'like', "%{$search}%")
                      ->orWhere('exchange', 'like', "%{$search}%")
                      ->orWhere('category', 'like', "%{$search}%");
                });
            }

            $total = Index::count();
            $filtered = $query->count();
            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;

            $data = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        }

        return view('admin.indices.index');
    }

    /**
     * Show the detailed history of an index.
     */
    public function show(Index $index)
    {
        $prices = $index->prices()->orderBy('traded_date', 'desc')->paginate(100);
        $holdingsPrice = $this->latestPriceWithHoldings($index->index_code);

        return view('admin.indices.show', compact('index', 'prices', 'holdingsPrice'));
    }

    /**
     * Edit index metadata.
     */
    public function edit(Index $index)
    {
        return view('admin.indices.edit', compact('index'));
    }

    /**
     * Update index metadata.
     */
    public function update(Request $request, Index $index)
    {
        $validated = $request->validate([
            'index_name' => 'required|string|max:255',
            'exchange'   => 'required|in:NSE,BSE',
            'category'   => 'nullable|string|max:255'
        ]);

        $index->update($validated);

        return redirect()->route('admin.indices.index')
            ->with('success', 'Index metadata updated successfully.');
    }

    /**
     * Display price history for indices.
     */
    public function prices(Request $request)
    {
        if ($request->ajax()) {
            $query = IndexPrice::query();

            // Handle Search
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('index_code', 'like', "%{$search}%")
                      ->orWhere('traded_date', 'like', "%{$search}%");
                });
            }

            if ($request->filled('index_code')) {
                $query->where('index_code', $request->index_code);
            }

            if ($request->filled('date_from')) {
                $query->where('traded_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('traded_date', '<=', $request->date_to);
            }

            $total = IndexPrice::count();
            $filtered = (clone $query)->count();

            $limit = $request->length ?? 100;
            $start = $request->start ?? 0;

            $data = $query->orderBy('traded_date', 'desc')
                ->skip($start)
                ->take($limit)
                ->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        }

        $indices = Index::select('index_code', 'index_name')->get();
        return view('admin.indices.prices', compact('indices'));
    }

    /**
     * Get single price record details (JSON).
     */
    public function priceDetail(IndexPrice $price)
    {
        $payload = $price->toArray();
        $payload['holdings_as_of'] = $price->traded_date?->format('Y-m-d');

        if (empty($price->holdings)) {
            $holdingsPrice = $this->latestPriceWithHoldings($price->index_code);
            if ($holdingsPrice) {
                $payload['holdings'] = $holdingsPrice->holdings;
                $payload['holdings_as_of'] = $holdingsPrice->traded_date?->format('Y-m-d');

                if (empty($payload['overview'])) {
                    $payload['overview'] = $holdingsPrice->overview;
                }
            }
        }

        return response()->json($payload);
    }

    private function latestPriceWithHoldings(string $indexCode): ?IndexPrice
    {
        return IndexPrice::query()
            ->where('index_code', $indexCode)
            ->whereNotNull('holdings')
            ->where('holdings', '!=', '[]')
            ->orderByDesc('traded_date')
            ->first();
    }

    /**
     * Trigger manual sync.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        try {
            $params = ['date' => $request->date];
            if ($request->filled('exchange')) {
                $params['--exchange'] = $request->exchange;
            }

            $exitCode = Artisan::call('indices:sync', $params);

            if ($exitCode === 0) {
                return response()->json([
                    'success' => true,
                    'message' => Artisan::output() ?: "Indices synced successfully."
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => "Sync failed with exit code {$exitCode}."
            ], 500);
        } catch (\Exception $e) {
            Log::error("Admin Index Sync Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export all indices to CSV.
     */
    public function export()
    {
        $fileName = 'indices_export_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['index_code', 'index_name', 'exchange', 'category'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            Index::chunk(1000, function ($indices) use ($file) {
                foreach ($indices as $index) {
                    fputcsv($file, [
                        $index->index_code,
                        $index->index_name,
                        $index->exchange,
                        $index->category
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import indices from CSV.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        try {
            $file = $request->file('file');
            $handle = fopen($file->getRealPath(), 'r');
            $header = fgetcsv($handle);

            if (!$header) {
                return response()->json(['success' => false, 'message' => 'Empty CSV file.'], 400);
            }

            $header = array_map('strtolower', array_map('trim', $header));
            $headerCount = count($header);
            
            $now = now();
            $records = [];
            $count = 0;

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) < 2) continue;

                $rowCount = count($row);
                if ($rowCount > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                } elseif ($rowCount < $headerCount) {
                    $row = array_pad($row, $headerCount, null);
                }

                $data = array_combine($header, $row);
                $code = trim($data['index_code'] ?? $data['code'] ?? '');
                
                if (empty($code)) continue;

                $records[] = [
                    'index_code' => $code,
                    'index_name' => trim($data['index_name'] ?? $data['name'] ?? ''),
                    'exchange'   => strtoupper(trim($data['exchange'] ?? 'NSE')),
                    'category'   => trim($data['category'] ?? ''),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($records) >= 200) {
                    Index::upsert($records, ['index_code'], ['index_name', 'exchange', 'category', 'updated_at']);
                    $count += count($records);
                    $records = [];
                }
            }

            if (count($records) > 0) {
                Index::upsert($records, ['index_code'], ['index_name', 'exchange', 'category', 'updated_at']);
                $count += count($records);
            }

            fclose($handle);

            return response()->json([
                'success' => true,
                'message' => "Successfully imported/updated {$count} index records."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display live synced index quotes.
     */
    public function quotes(Request $request, UpstoxInstrumentSyncService $syncService)
    {
        if (Index::query()->whereNotNull('upstox_instrument_key')->where('upstox_instrument_key', '<>', '')->doesntExist()) {
            $syncService->ensureIndexInstrumentKeys();
        }

        $filters = $request->validate([
            'search'    => 'nullable|string|max:100',
            'exchange'  => 'nullable|in:NSE,BSE',
            'category'  => 'nullable|string|max:100',
            'freshness' => 'nullable|in:recent,stale,pending',
        ]);

        $cutoff = now()->utc()->subSeconds(900)->format('Y-m-d H:i:s');
        $query = DB::table('indices as i')
            ->leftJoin('index_quotes as q', 'i.index_code', '=', 'q.index_code')
            ->whereNotNull('i.upstox_instrument_key')
            ->where('i.upstox_instrument_key', '<>', '');

        if ($search = trim($filters['search'] ?? '')) {
            $query->where(function ($q) use ($search) {
                $q->where('i.index_code', 'like', "%{$search}%")
                  ->orWhere('i.index_name', 'like', "%{$search}%")
                  ->orWhere('i.category', 'like', "%{$search}%");
            });
        }

        if (!empty($filters['exchange'])) {
            $query->where('i.exchange', strtoupper($filters['exchange']));
        }

        if (!empty($filters['category'])) {
            $query->where('i.category', $filters['category']);
        }

        if (!empty($filters['freshness'])) {
            if ($filters['freshness'] === 'recent') {
                $query->where('q.quoted_at', '>=', $cutoff);
            } elseif ($filters['freshness'] === 'stale') {
                $query->whereNotNull('q.price')
                      ->where(function ($q) use ($cutoff) {
                          $q->whereNull('q.quoted_at')->orWhere('q.quoted_at', '<', $cutoff);
                      });
            } elseif ($filters['freshness'] === 'pending') {
                $query->whereNull('q.price');
            }
        }

        $stats = [
            'total'        => DB::table('indices')->whereNotNull('upstox_instrument_key')->where('upstox_instrument_key', '<>', '')->count(),
            'recent'       => DB::table('index_quotes')->where('quoted_at', '>=', $cutoff)->count(),
            'gainers'      => DB::table('index_quotes')->where('change', '>', 0)->count(),
            'losers'       => DB::table('index_quotes')->where('change', '<', 0)->count(),
            'last_fetched' => DB::table('index_quotes')->max('fetched_at'),
        ];

        $quotes = $query->select(
            'i.index_code',
            'i.index_name',
            'i.exchange',
            'i.category',
            'i.upstox_instrument_key',
            'q.price',
            'q.previous_close',
            'q.change',
            'q.change_percent',
            'q.volume',
            'q.quoted_at',
            'q.fetched_at',
            'q.payload'
        )
        ->orderByRaw("CASE WHEN q.price IS NOT NULL THEN 0 ELSE 1 END")
        ->orderByDesc('q.fetched_at')
        ->orderBy('i.index_code')
        ->paginate(25)
        ->withQueryString();

        foreach ($quotes as $quote) {
            $payload = $quote->payload;
            $quote->details = $payload ? (json_decode($payload, true) ?: []) : [];
            $quote->quoted_time = $quote->quoted_at
                ? Carbon::parse($quote->quoted_at, 'UTC')->timezone('Asia/Kolkata')
                : null;
            $quote->fetched_time = $quote->fetched_at
                ? Carbon::parse($quote->fetched_at, 'UTC')->timezone('Asia/Kolkata')
                : null;
            $quote->is_stale = !$quote->quoted_at || $quote->quoted_at < $cutoff;
        }

        $categories = DB::table('indices')->whereNotNull('category')->distinct()->pluck('category');

        return view('admin.indices.quotes', compact('quotes', 'filters', 'stats', 'categories'));
    }

    /**
     * Manually trigger live index quotes synchronization.
     */
    public function syncQuotes(UpstoxInstrumentSyncService $syncService)
    {
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        try {
            if (Index::query()->whereNotNull('upstox_instrument_key')->where('upstox_instrument_key', '<>', '')->doesntExist()) {
                $syncService->ensureIndexInstrumentKeys();
            }

            $exitCode = Artisan::call('market:sync-upstox-index-quotes', ['--batch-size' => 500]);
            $output = trim(Artisan::output());
        } catch (\Throwable $exception) {
            return redirect()->route('admin.indices.quotes')->with(
                'error',
                'Index quote sync could not start: ' . $exception->getMessage()
            );
        }

        $summary = last(array_filter(explode("\n", $output))) ?: '';

        return redirect()->route('admin.indices.quotes')->with(
            $exitCode === 0 ? 'success' : 'error',
            $exitCode === 0
                ? 'Latest index quotes synced successfully. ' . $summary
                : 'Index quote sync failed. ' . ($output ?: 'No eligible index quotes returned.')
        );
    }

}
