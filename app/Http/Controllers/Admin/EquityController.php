<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Equity;
use App\Models\EquityPrice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class EquityController extends Controller
{
    /**
     * Display a listing of equities.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Equity::query();

            // Handle Search
            if ($request->has('search') && !empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('isin', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%")
                      ->orWhere('nse_symbol', 'like', "%{$search}%")
                      ->orWhere('bse_symbol', 'like', "%{$search}%")
                      ->orWhere('industry', 'like', "%{$search}%")
                      ->orWhere('market_cap', 'like', "%{$search}%");
                });
            }

            $total = Equity::count();
            $filtered = $query->count();
            $limit = $request->length ?? 25;
            $start = $request->start ?? 0;

            $data = $query->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
                'data' => $data,
            ]);
        }

        return view('equities.index');
    }

    /**
     * Display price records.
     */
    public function prices()
    {
        return view('equities.prices');
    }

    /**
     * Data provider for prices table.
     */
    public function pricesData(Request $request)
    {
        $query = EquityPrice::with('equity:id,company_name,nse_symbol,bse_symbol,market_cap');

        // Handle Search (Global)
        if ($request->has('search') && !empty($request->search['value'])) {
            $search = $request->search['value'];
            $query->where(function ($q) use ($search) {
                $q->where('isin', 'like', "%{$search}%")
                  ->orWhere('traded_date', 'like', "%{$search}%")
                  ->orWhereHas('equity', function($eq) use ($search) {
                      $eq->where('company_name', 'like', "%{$search}%")
                         ->orWhere('nse_symbol', 'like', "%{$search}%")
                         ->orWhere('bse_symbol', 'like', "%{$search}%");
                  });
            });
        }
 
        // Handle Specific Filters
        if ($request->filled('date_from')) {
            $query->where('traded_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('traded_date', '<=', $request->date_to);
        }
        if ($request->filled('isin')) {
            $value = $request->isin;
            $query->where(function($q) use ($value) {
                $q->where('isin', 'like', "%{$value}%")
                  ->orWhereHas('equity', function($eq) use ($value) {
                      $eq->where('company_name', 'like', "%{$value}%")
                         ->orWhere('nse_symbol', 'like', "%{$value}%")
                         ->orWhere('bse_symbol', 'like', "%{$value}%");
                  });
            });
        }

        $total = EquityPrice::count();
        $filtered = (clone $query)->count();

        // Handle Dynamic Sorting from DataTables
        $columns = [
            0 => 'traded_date',
            1 => 'name',
            2 => 'isin',
            3 => 'nse_open',
            4 => 'nse_close',
            5 => 'nse_volume',
            6 => 'bse_open',
            7 => 'bse_close',
            8 => 'bse_volume',
            9 => 'spread'
        ];

        if ($request->has('order')) {
            $colIdx = $request->order[0]['column'];
            $colDir = $request->order[0]['dir'];
            $colName = $columns[$colIdx] ?? 'traded_date';
            
            if ($colName === 'name') {
                $query->join('equities', 'equity_prices.equity_id', '=', 'equities.id')
                      ->orderBy('equities.company_name', $colDir)
                      ->select('equity_prices.*');
            } else {
                $query->orderBy($colName, $colDir);
            }
        } else {
            $query->orderBy('traded_date', 'desc');
        }

        $limit = $request->length ?? 25;
        $start = $request->start ?? 0;
        $data = $query->skip($start)->take($limit)->get();

        return response()->json([
            'draw' => $request->draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $filtered,
            'data' => $data,
        ]);
    }

    /**
     * Display a specific price record (AJAX).
     */
    public function priceDetail(EquityPrice $price)
    {
        $price->load('equity:id,company_name,isin,nse_symbol,bse_symbol');
        return response()->json($price);
    }

    /**
     * Show the form for editing the specified equity.
     */
    public function edit(Equity $equity)
    {
        return view('equities.edit', compact('equity'));
    }

    /**
     * Update the specified equity in storage.
     */
    public function update(Request $request, Equity $equity)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'nse_symbol' => 'nullable|string|max:50',
            'bse_symbol' => 'nullable|string|max:50',
            'industry' => 'nullable|string|max:255',
            'market_cap' => 'nullable|string|max:100',
            'market_cap_category' => 'nullable|string|max:100',
            'face_value' => 'nullable|numeric',
            'listing_date' => 'nullable|date',
            'is_active' => 'required|boolean',
        ]);

        $equity->update($request->all());

        return redirect()->route('equities.index')->with('success', 'Equity updated successfully.');
    }

    /**
     * Display the specified equity and its price history.
     */
    public function show(Request $request, Equity $equity)
    {
        if ($request->ajax()) {
            $query = $equity->prices();
            
            $total = $query->count();
            $limit = $request->length ?? 25;
            $start = $request->start ?? 0;

            $data = $query->orderBy('traded_date', 'desc')->skip($start)->take($limit)->get();

            return response()->json([
                'draw' => $request->draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'data' => $data,
            ]);
        }

        $prices = $equity->prices()->orderBy('traded_date', 'desc')->paginate(50);
        return view('equities.show', compact('equity', 'prices'));
    }

    /**
     * Sync data for a specific date.
     */
    public function sync(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'exchange' => 'nullable|string|in:NSE,BSE',
        ]);
 
        $date = $request->date;
        $exchange = $request->exchange;
 
        set_time_limit(300); // Increase timeout to 5 minutes

        try {
            // Trigger the console command
            $exitCode = Artisan::call('equities:sync', [
                'date' => $date,
                'exchange' => $exchange
            ]);
 
            if ($exitCode === 0) {
                // Return the actual success message from command output (e.g. Sync completed successfully for 2024-04-10)
                $output = Artisan::output();
                $lines = array_filter(explode("\n", trim($output)));
                $msg = end($lines) ?: "Equity data synced successfully.";

                return response()->json([
                    'success' => true, 
                    'message' => $msg
                ]);
            } else {
                $output = Artisan::output();
                return response()->json([
                    'success' => false, 
                    'message' => "Sync failed with exit code {$exitCode}.",
                    'debug' => $output
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Equity Sync Error: " . $e->getMessage());
            return response()->json([
                'success' => false, 
                'message' => "An error occurred: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export all equities to CSV.
     */
    public function export()
    {
        $fileName = 'equities_export_' . date('Y-m-d') . '.csv';
        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $columns = ['isin', 'company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category', 'face_value', 'listing_date', 'is_active'];

        $callback = function() use($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            Equity::chunk(1000, function ($equities) use ($file) {
                foreach ($equities as $equity) {
                    fputcsv($file, [
                        $equity->isin,
                        $equity->company_name,
                        $equity->nse_symbol,
                        $equity->bse_symbol,
                        $equity->industry,
                        $equity->market_cap,
                        $equity->market_cap_category,
                        $equity->face_value,
                        $equity->listing_date ? $equity->listing_date->format('Y-m-d') : '',
                        $equity->is_active ? '1' : '0',
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import equities from CSV.
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

            // Normalize header
            $header = array_map('strtolower', array_map('trim', $header));
            $headerCount = count($header);
            
            $now = now();
            $records = [];
            $count = 0;

            while (($row = fgetcsv($handle)) !== FALSE) {
                if (count($row) < 2) continue; // Skip empty rows

                // Balance row and header count if they mismatch
                $rowCount = count($row);
                if ($rowCount > $headerCount) {
                    $row = array_slice($row, 0, $headerCount);
                } elseif ($rowCount < $headerCount) {
                    $row = array_pad($row, $headerCount, null);
                }

                $data = array_combine($header, $row);
                $isin = trim($data['isin'] ?? '');
                
                if (empty($isin)) continue;

                $records[] = [
                    'isin' => $isin,
                    'company_name' => trim($data['company_name'] ?? $data['name'] ?? ''),
                    'nse_symbol' => trim($data['nse_symbol'] ?? $data['symbol'] ?? ''),
                    'bse_symbol' => trim($data['bse_symbol'] ?? ''),
                    'industry' => trim($data['industry'] ?? ''),
                    'market_cap' => trim($data['market_cap'] ?? ''),
                    'market_cap_category' => trim($data['market_cap_category'] ?? ''),
                    'face_value' => !empty($data['face_value']) ? (float)$data['face_value'] : null,
                    'listing_date' => (function() use ($data) {
                        if (empty($data['listing_date'])) return null;
                        try {
                            return \Carbon\Carbon::parse($data['listing_date'])->format('Y-m-d');
                        } catch (\Exception $e) {
                            return null;
                        }
                    })(),
                    'is_active' => (isset($data['is_active']) && ($data['is_active'] === '0' || strtolower($data['is_active']) === 'false' || strtolower($data['is_active']) === 'no')) ? false : true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($records) >= 200) {
                    Equity::upsert($records, ['isin'], ['company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category', 'face_value', 'listing_date', 'is_active', 'updated_at']);
                    $count += count($records);
                    $records = [];
                }
            }

            if (count($records) > 0) {
                Equity::upsert($records, ['isin'], ['company_name', 'nse_symbol', 'bse_symbol', 'industry', 'market_cap', 'market_cap_category', 'face_value', 'listing_date', 'is_active', 'updated_at']);
                $count += count($records);
            }

            fclose($handle);

            return response()->json([
                'success' => true,
                'message' => "Successfully imported/updated {$count} equity records."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
