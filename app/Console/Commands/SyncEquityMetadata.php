<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Equity;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Log;

class SyncEquityMetadata extends Command
{
    protected $signature = 'equities:sync-metadata {--force : Force download even if file exists}';
    protected $description = 'Sync Equity metadata (Industry, Market Cap, Category) from AMFI and NiftyIndices';

    public function handle()
    {
        $this->info("Starting Equity Metadata Sync...");

        // 1. Download AMFI XLSX for Market Cap and Industry
        $this->syncFromAmfi();

        // 2. Sync from Nifty Total Market (Industry & Symbol)
        $this->syncFromNiftyList('https://archives.nseindia.com/content/indices/ind_niftytotalmarket_list.csv', 'Nifty Total Market');

        // 3. Mark Categories based on Nifty Index membership (for those not in AMFI)
        $this->updateCategories();

        // 4. Sync Face Value and Listing Date from NSE Master
        $this->syncFromNseMaster();

        $this->info("Metadata sync complete.");
        return 0;
    }

    private function syncFromAmfi()
    {
        $this->info("Fetching AMFI Categorization file...");

        $fileName = 'amfi_stocks.xlsx';
        $path = storage_path('app/' . $fileName);

        try {
            if (!file_exists($path) || $this->option('force')) {
                $this->info("  Finding latest AMFI URL...");

                $pageUrl = 'https://www.amfiindia.com/research-information/other-data/categorization-of-stocks';
                $pageHtml = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])->withoutVerifying()->get($pageUrl)->body();

                if (preg_match('/href=["\']([^"\']+Categorization[_\-]?of[_\-]?Stocks[^"\']+\.xlsx)["\']/i', $pageHtml, $matches)) {
                    $url = $matches[1];
                    if (!str_starts_with($url, 'http')) {
                        $url = 'https://www.amfiindia.com' . (str_starts_with($url, '/') ? '' : '/') . $url;
                    }
                    $this->info("  Found URL: $url");
                } else {
                    $this->warn("  Could not scrape latest AMFI URL. Skipping AMFI sync.");
                    return;
                }

                $this->info("  Downloading XLSX...");
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                ])->withoutVerifying()->get($url);

                if ($response->failed()) {
                    $this->warn("  Failed to download AMFI file. Status: " . $response->status());
                    return;
                }

                file_put_contents($path, $response->body());
            }

            $this->info("  Parsing AMFI data...");
            $data = Excel::toArray([], $path);
            $rows = $data[0] ?? [];

            if (empty($rows)) return;

            // Find header row
            $headerIndex = -1;
            foreach ($rows as $index => $row) {
                $rowStr = implode(' ', array_map('strtolower', array_filter($row)));
                if (str_contains($rowStr, 'isin')) {
                    $headerIndex = $index;
                    break;
                }
            }

            if ($headerIndex === -1) return;

            $headers = array_map('strtolower', array_map('trim', $rows[$headerIndex]));
            $colMap = [
                'isin'     => array_search('isin', $headers) ?: array_search('isin code', $headers),
                'name'     => array_search('company name', $headers),
                'mcap'     => -1,
                'category' => array_search('category', $headers),
            ];

            foreach ($headers as $idx => $h) {
                if (str_contains($h, 'average market capitalization')) $colMap['mcap'] = $idx;
            }

            $updated = 0;
            foreach ($rows as $index => $row) {
                if ($index <= $headerIndex) continue;

                $isin = trim($row[$colMap['isin']] ?? '');
                if (empty($isin)) continue;

                $mcapValue = $row[$colMap['mcap']] ?? 0;
                $mcap = is_numeric($mcapValue) ? (float)$mcapValue : (float)str_replace(',', '', $mcapValue);
                $category = trim($row[$colMap['category']] ?? '');

                $equity = Equity::firstOrNew(['isin' => $isin]);
                $needsUpdate = !$equity->exists;

                if ($mcap && $equity->market_cap != $mcap) {
                    $equity->market_cap = $mcap;
                    $needsUpdate = true;
                }

                if ($category && $equity->market_cap_category != $category) {
                    $equity->market_cap_category = $category;
                    $needsUpdate = true;
                }

                $name = trim($row[$colMap['name']] ?? '');
                if ($name && empty($equity->company_name)) {
                    $equity->company_name = $name;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $equity->save();
                    $updated++;
                }
            }
            $this->info("  AMFI: Updated $updated equities.");
        } catch (\Exception $e) {
            $this->error("  Error in AMFI sync: " . $e->getMessage());
        }
    }

    private function syncFromNiftyList($url, $label)
    {
        $this->info("Fetching $label list...");
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://www.nseindia.com/',
            ])->timeout(30)->withoutVerifying()->get($url);
            if ($response->failed()) return;

            $csvData = trim($response->body());
            $lines = explode("\n", str_replace("\r", "", $csvData));
            $header = str_getcsv(array_shift($lines));
            $map = array_flip(array_map('trim', $header));

            $updated = 0;
            foreach ($lines as $line) {
                $row = str_getcsv($line);
                if (count($row) < count($header)) continue;

                $symbol = trim($row[$map['Symbol'] ?? -1] ?? '');
                $isin = trim($row[$map['ISIN Code'] ?? $map['ISIN'] ?? -1] ?? '');
                $industry = trim($row[$map['Industry'] ?? -1] ?? '');

                if (empty($isin)) continue;

                $equity = Equity::firstOrNew(['isin' => $isin]);
                $needsUpdate = !$equity->exists;

                if ($industry && $equity->industry != $industry) {
                    $equity->industry = $industry;
                    $needsUpdate = true;
                }

                if ($symbol && $equity->nse_symbol != $symbol) {
                    $equity->nse_symbol = $symbol;
                    $needsUpdate = true;
                }

                $companyName = trim($row[$map['Company Name'] ?? -1] ?? '');
                if ($companyName && empty($equity->company_name)) {
                    $equity->company_name = $companyName;
                    $needsUpdate = true;
                }

                if ($needsUpdate) {
                    $equity->save();
                    $updated++;
                }
            }
            $this->info("  $label: Updated $updated equities.");
        } catch (\Exception $e) {
        }
    }

    private function updateCategories()
    {
        $this->info("Refining Categories...");

        $indices = [
            ['url' => 'https://archives.nseindia.com/content/indices/ind_nifty100list.csv', 'cat' => 'Large Cap'],
            ['url' => 'https://archives.nseindia.com/content/indices/ind_niftymidcap150list.csv', 'cat' => 'Mid Cap'],
            ['url' => 'https://archives.nseindia.com/content/indices/ind_niftysmallcap250list.csv', 'cat' => 'Small Cap'],
        ];

        foreach ($indices as $index) {
            $this->markCategory($index['url'], $index['cat']);
        }

        Equity::whereNull('market_cap_category')->whereNotNull('nse_symbol')->update(['market_cap_category' => 'Small Cap']);
    }

    private function markCategory($url, $category)
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://www.nseindia.com/',
            ])->timeout(30)->withoutVerifying()->get($url);
            if ($response->successful()) {
                $lines = explode("\n", str_replace("\r", "", trim($response->body())));
                $header = str_getcsv(array_shift($lines));
                $map = array_flip(array_map('trim', $header));

                $updated = 0;
                foreach ($lines as $line) {
                    $row = str_getcsv($line);
                    if (count($row) < 3) continue;

                    $isin = trim($row[$map['ISIN Code'] ?? $map['ISIN'] ?? 4] ?? '');
                    $industry = trim($row[$map['Industry'] ?? -1] ?? '');

                    if (strlen($isin) === 12) {
                        $equity = Equity::firstOrNew(['isin' => $isin]);
                        $needsUpdate = !$equity->exists;

                        if ($category && $equity->market_cap_category != $category) {
                            $equity->market_cap_category = $category;
                            $needsUpdate = true;
                        }

                        if ($industry && $equity->industry != $industry) {
                            $equity->industry = $industry;
                            $needsUpdate = true;
                        }

                        $symbol = trim($row[$map['Symbol'] ?? -1] ?? '');
                        if ($symbol && $equity->nse_symbol != $symbol) {
                            $equity->nse_symbol = $symbol;
                            $needsUpdate = true;
                        }

                        $companyName = trim($row[$map['Company Name'] ?? -1] ?? '');
                        if ($companyName && empty($equity->company_name)) {
                            $equity->company_name = $companyName;
                            $needsUpdate = true;
                        }

                        if ($needsUpdate) {
                            $equity->save();
                            $updated++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
        }
    }

    private function syncFromNseMaster()
    {
        $this->info("Fetching NSE Master List for Face Value & Listing Date...");

        $urls = [
            'https://nsearchives.nseindia.com/content/equities/EQUITY_L.csv',
            'https://archives.nseindia.com/content/equities/EQUITY_L.csv',
        ];

        $csvData = null;
        foreach ($urls as $url) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer' => 'https://www.nseindia.com/',
                ])->timeout(30)->withoutVerifying()->get($url);

                if ($response->successful()) {
                    $csvData = trim($response->body());
                    if (strlen($csvData) > 500) {
                        break;
                    }
                }
            } catch (\Exception $e) {
                // Ignore and try the next URL
            }
        }

        if (!$csvData) {
            $this->warn("  Failed to download NSE Master file.");
            return;
        }

        $lines = explode("\n", str_replace("\r", "", $csvData));
        $headerLine = array_shift($lines);
        $header = array_map('trim', str_getcsv($headerLine));
        $map = array_flip($header);

        $updated = 0;
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            if (count($row) < 5) continue;

            $isin = trim($row[$map['ISIN NUMBER'] ?? -1] ?? '');
            $faceValueStr = trim($row[$map['FACE VALUE'] ?? -1] ?? '');
            $listingDateStr = trim($row[$map['DATE OF LISTING'] ?? -1] ?? '');

            $symbol = trim($row[$map['SYMBOL'] ?? -1] ?? '');
            $companyName = trim($row[$map['NAME OF COMPANY'] ?? -1] ?? '');
            $series = trim($row[$map['SERIES'] ?? -1] ?? '');
            $marketLotStr = trim($row[$map['MARKET LOT'] ?? -1] ?? '');

            if (empty($isin)) continue;

            $equity = Equity::firstOrNew(['isin' => $isin]);
            $needsUpdate = !$equity->exists;

            if (is_numeric($faceValueStr) && $equity->face_value != (float)$faceValueStr) {
                $equity->face_value = (float)$faceValueStr;
                $needsUpdate = true;
            }

            if (!empty($series) && $equity->series != $series) {
                $equity->series = $series;
                $needsUpdate = true;
            }

            if (is_numeric($marketLotStr) && $equity->market_lot != (int)$marketLotStr) {
                $equity->market_lot = (int)$marketLotStr;
                $needsUpdate = true;
            }

            if (!empty($listingDateStr)) {
                try {
                    $date = Carbon::createFromFormat('d-M-Y', $listingDateStr)->format('Y-m-d');
                    if ($equity->listing_date != $date) {
                        $equity->listing_date = $date;
                        $needsUpdate = true;
                    }
                } catch (\Exception $e) {
                    try {
                        $date = Carbon::parse($listingDateStr)->format('Y-m-d');
                        if ($equity->listing_date != $date) {
                            $equity->listing_date = $date;
                            $needsUpdate = true;
                        }
                    } catch (\Exception $e2) {
                        // ignore invalid dates
                    }
                }
            }

            if (!empty($symbol) && $equity->nse_symbol != $symbol) {
                $equity->nse_symbol = $symbol;
                $needsUpdate = true;
            }

            if (!empty($companyName) && empty($equity->company_name)) {
                $equity->company_name = $companyName;
                $needsUpdate = true;
            }

            if ($needsUpdate) {
                $equity->is_active = true; // Ensure active if newly created
                $equity->save();
                $updated++;
            }
        }

        $this->info("  NSE Master: Updated $updated equities.");
    }
}
