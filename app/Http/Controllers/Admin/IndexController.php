<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Index;
use App\Models\IndexPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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
        return view('admin.indices.show', compact('index', 'prices'));
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
        return response()->json($price);
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
}
