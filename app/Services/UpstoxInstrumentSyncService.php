<?php

namespace App\Services;

use App\Support\TlsCaBundle;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class UpstoxInstrumentSyncService
{
    private const SEGMENTS = [
        'NSE_EQ' => 'nse',
        'BSE_EQ' => 'bse',
    ];

    private const INDEX_SEGMENTS = [
        'NSE_INDEX' => 'NSE',
        'BSE_INDEX' => 'BSE',
    ];

    public const DEFAULT_INDICES = [
        [
            'index_code' => 'NIFTY_50',
            'index_name' => 'Nifty 50',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 50',
            'aliases' => ['NIFTY 50', 'NIFTY50', 'CNX NIFTY'],
        ],
        [
            'index_code' => 'NIFTY_BANK',
            'index_name' => 'Nifty Bank',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Bank',
            'aliases' => ['NIFTY BANK', 'NIFTYBANK', 'BANK NIFTY', 'BANKNIFTY'],
        ],
        [
            'index_code' => 'NIFTY_NEXT_50',
            'index_name' => 'Nifty Next 50',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Next 50',
            'aliases' => ['NIFTY NEXT 50', 'NIFTYNEXT50', 'JUNIOR NIFTY'],
        ],
        [
            'index_code' => 'NIFTY_100',
            'index_name' => 'Nifty 100',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 100',
            'aliases' => ['NIFTY 100', 'NIFTY100'],
        ],
        [
            'index_code' => 'NIFTY_200',
            'index_name' => 'Nifty 200',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 200',
            'aliases' => ['NIFTY 200', 'NIFTY200'],
        ],
        [
            'index_code' => 'NIFTY_500',
            'index_name' => 'Nifty 500',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty 500',
            'aliases' => ['NIFTY 500', 'NIFTY500'],
        ],
        [
            'index_code' => 'NIFTY_MIDCAP_50',
            'index_name' => 'Nifty Midcap 50',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Midcap 50',
            'aliases' => ['NIFTY MIDCAP 50', 'NIFTYMIDCAP50'],
        ],
        [
            'index_code' => 'NIFTY_MIDCAP_100',
            'index_name' => 'Nifty Midcap 100',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Midcap 100',
            'aliases' => ['NIFTY MIDCAP 100', 'NIFTYMIDCAP100'],
        ],
        [
            'index_code' => 'NIFTY_MIDCAP_150',
            'index_name' => 'Nifty Midcap 150',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Midcap 150',
            'aliases' => ['NIFTY MIDCAP 150', 'NIFTYMIDCAP150'],
        ],
        [
            'index_code' => 'NIFTY_SMALLCAP_50',
            'index_name' => 'Nifty Smallcap 50',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Smallcap 50',
            'aliases' => ['NIFTY SMALLCAP 50', 'NIFTYSMALLCAP50'],
        ],
        [
            'index_code' => 'NIFTY_SMALLCAP_100',
            'index_name' => 'Nifty Smallcap 100',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Smallcap 100',
            'aliases' => ['NIFTY SMALLCAP 100', 'NIFTYSMALLCAP100'],
        ],
        [
            'index_code' => 'NIFTY_SMALLCAP_250',
            'index_name' => 'Nifty Smallcap 250',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Smallcap 250',
            'aliases' => ['NIFTY SMALLCAP 250', 'NIFTYSMALLCAP250'],
        ],
        [
            'index_code' => 'NIFTY_MIDSMALLCAP_400',
            'index_name' => 'Nifty Midsmallcap 400',
            'exchange' => 'NSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Midsmallcap 400',
            'aliases' => ['NIFTY MIDSMALLCAP 400', 'NIFTYMIDSMALLCAP400'],
        ],
        [
            'index_code' => 'NIFTY_IT',
            'index_name' => 'Nifty IT',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty IT',
            'aliases' => ['NIFTY IT', 'NIFTYIT'],
        ],
        [
            'index_code' => 'NIFTY_AUTO',
            'index_name' => 'Nifty Auto',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Auto',
            'aliases' => ['NIFTY AUTO', 'NIFTYAUTO'],
        ],
        [
            'index_code' => 'NIFTY_FIN_SERVICE',
            'index_name' => 'Nifty Financial Services',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Financial Services',
            'aliases' => ['NIFTY FINANCIAL SERVICES', 'NIFTY FIN SERVICE', 'FINNIFTY', 'NIFTY_FINANCIAL_SERVICES'],
        ],
        [
            'index_code' => 'NIFTY_FMCG',
            'index_name' => 'Nifty FMCG',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty FMCG',
            'aliases' => ['NIFTY FMCG', 'NIFTYFMCG'],
        ],
        [
            'index_code' => 'NIFTY_MEDIA',
            'index_name' => 'Nifty Media',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Media',
            'aliases' => ['NIFTY MEDIA', 'NIFTYMEDIA'],
        ],
        [
            'index_code' => 'NIFTY_METAL',
            'index_name' => 'Nifty Metal',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Metal',
            'aliases' => ['NIFTY METAL', 'NIFTYMETAL'],
        ],
        [
            'index_code' => 'NIFTY_PHARMA',
            'index_name' => 'Nifty Pharma',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Pharma',
            'aliases' => ['NIFTY PHARMA', 'NIFTYPHARMA'],
        ],
        [
            'index_code' => 'NIFTY_PSU_BANK',
            'index_name' => 'Nifty PSU Bank',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty PSU Bank',
            'aliases' => ['NIFTY PSU BANK', 'NIFTYPSUBANK'],
        ],
        [
            'index_code' => 'NIFTY_PVT_BANK',
            'index_name' => 'Nifty Private Bank',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Private Bank',
            'aliases' => ['NIFTY PRIVATE BANK', 'NIFTY PVT BANK', 'NIFTY_PRIVATE_BANK'],
        ],
        [
            'index_code' => 'NIFTY_REALTY',
            'index_name' => 'Nifty Realty',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Realty',
            'aliases' => ['NIFTY REALTY', 'NIFTYREALTY'],
        ],
        [
            'index_code' => 'NIFTY_HEALTHCARE',
            'index_name' => 'Nifty Healthcare',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Healthcare',
            'aliases' => ['NIFTY HEALTHCARE', 'NIFTYHEALTHCARE'],
        ],
        [
            'index_code' => 'NIFTY_CONSR_DURBL',
            'index_name' => 'Nifty Consumer Durables',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Consumer Durables',
            'aliases' => ['NIFTY CONSUMER DURABLES', 'NIFTY CONSR DURBL', 'NIFTY_CONSUMER_DURABLES'],
        ],
        [
            'index_code' => 'NIFTY_OIL_AND_GAS',
            'index_name' => 'Nifty Oil and Gas',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Oil and Gas',
            'aliases' => ['NIFTY OIL & GAS', 'NIFTY OIL AND GAS', 'NIFTY_OIL_AND_GAS', 'NIFTY_OIL_GAS'],
        ],
        [
            'index_code' => 'NIFTY_ENERGY',
            'index_name' => 'Nifty Energy',
            'exchange' => 'NSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Energy',
            'aliases' => ['NIFTY ENERGY', 'NIFTYENERGY'],
        ],
        [
            'index_code' => 'NIFTY_INFRA',
            'index_name' => 'Nifty Infra',
            'exchange' => 'NSE',
            'category' => 'Thematic',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Infra',
            'aliases' => ['NIFTY INFRA', 'NIFTYINFRA'],
        ],
        [
            'index_code' => 'NIFTY_COMMODITIES',
            'index_name' => 'Nifty Commodities',
            'exchange' => 'NSE',
            'category' => 'Thematic',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty Commodities',
            'aliases' => ['NIFTY COMMODITIES', 'NIFTYCOMMODITIES'],
        ],
        [
            'index_code' => 'NIFTY_INDIA_MFG',
            'index_name' => 'Nifty India Manufacturing',
            'exchange' => 'NSE',
            'category' => 'Thematic',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty India Manufacturing',
            'aliases' => ['NIFTY INDIA MANUFACTURING', 'NIFTY INDIA MFG'],
        ],
        [
            'index_code' => 'NIFTY_CPSE',
            'index_name' => 'Nifty CPSE',
            'exchange' => 'NSE',
            'category' => 'Thematic',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty CPSE',
            'aliases' => ['NIFTY CPSE', 'NIFTYCPSE'],
        ],
        [
            'index_code' => 'NIFTY_PSE',
            'index_name' => 'Nifty PSE',
            'exchange' => 'NSE',
            'category' => 'Thematic',
            'upstox_instrument_key' => 'NSE_INDEX|Nifty PSE',
            'aliases' => ['NIFTY PSE', 'NIFTYPSE'],
        ],
        [
            'index_code' => 'INDIA_VIX',
            'index_name' => 'India VIX',
            'exchange' => 'NSE',
            'category' => 'Volatility',
            'upstox_instrument_key' => 'NSE_INDEX|India VIX',
            'aliases' => ['INDIA VIX', 'INDIAVIX', 'VIX'],
        ],
        // BSE
        [
            'index_code' => 'BSE_SENSEX',
            'index_name' => 'S&P BSE SENSEX',
            'exchange' => 'BSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'BSE_INDEX|SENSEX',
            'aliases' => ['SENSEX', 'BSE SENSEX', 'S&P BSE SENSEX', 'BSESN'],
        ],
        [
            'index_code' => 'BSE_100',
            'index_name' => 'S&P BSE 100',
            'exchange' => 'BSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'BSE_INDEX|BSE 100',
            'aliases' => ['BSE 100', 'S&P BSE 100', 'BSE100'],
        ],
        [
            'index_code' => 'BSE_200',
            'index_name' => 'S&P BSE 200',
            'exchange' => 'BSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'BSE_INDEX|BSE 200',
            'aliases' => ['BSE 200', 'S&P BSE 200', 'BSE200'],
        ],
        [
            'index_code' => 'BSE_500',
            'index_name' => 'S&P BSE 500',
            'exchange' => 'BSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'BSE_INDEX|BSE 500',
            'aliases' => ['BSE 500', 'S&P BSE 500', 'BSE500'],
        ],
        [
            'index_code' => 'BSE_MIDCAP',
            'index_name' => 'S&P BSE MidCap',
            'exchange' => 'BSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'BSE_INDEX|BSE MidCap',
            'aliases' => ['BSE MIDCAP', 'S&P BSE MIDCAP', 'BSEMIDCAP'],
        ],
        [
            'index_code' => 'BSE_SMALLCAP',
            'index_name' => 'S&P BSE SmallCap',
            'exchange' => 'BSE',
            'category' => 'Broad-based',
            'upstox_instrument_key' => 'BSE_INDEX|BSE SmallCap',
            'aliases' => ['BSE SMALLCAP', 'S&P BSE SMALLCAP', 'BSESMALLCAP'],
        ],
        [
            'index_code' => 'BSE_BANKEX',
            'index_name' => 'S&P BSE BANKEX',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Bankex',
            'aliases' => ['BSE BANKEX', 'S&P BSE BANKEX', 'BANKEX'],
        ],
        [
            'index_code' => 'BSE_AUTO',
            'index_name' => 'S&P BSE Auto',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Auto',
            'aliases' => ['BSE AUTO', 'S&P BSE AUTO', 'BSEAUTO'],
        ],
        [
            'index_code' => 'BSE_FMCG',
            'index_name' => 'S&P BSE FMCG',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE FMCG',
            'aliases' => ['BSE FMCG', 'S&P BSE FMCG', 'BSEFMCG'],
        ],
        [
            'index_code' => 'BSE_IT',
            'index_name' => 'S&P BSE IT',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE IT',
            'aliases' => ['BSE IT', 'S&P BSE IT', 'BSEIT'],
        ],
        [
            'index_code' => 'BSE_HEALTHCARE',
            'index_name' => 'S&P BSE Healthcare',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Healthcare',
            'aliases' => ['BSE HEALTHCARE', 'S&P BSE HEALTHCARE'],
        ],
        [
            'index_code' => 'BSE_METAL',
            'index_name' => 'S&P BSE Metal',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Metal',
            'aliases' => ['BSE METAL', 'S&P BSE METAL'],
        ],
        [
            'index_code' => 'BSE_OIL_GAS',
            'index_name' => 'S&P BSE Oil & Gas',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Oil & Gas',
            'aliases' => ['BSE OIL & GAS', 'BSE OIL AND GAS', 'BSE_OIL_AND_GAS'],
        ],
        [
            'index_code' => 'BSE_POWER',
            'index_name' => 'S&P BSE Power',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Power',
            'aliases' => ['BSE POWER', 'S&P BSE POWER'],
        ],
        [
            'index_code' => 'BSE_REALTY',
            'index_name' => 'S&P BSE Realty',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Realty',
            'aliases' => ['BSE REALTY', 'S&P BSE REALTY'],
        ],
        [
            'index_code' => 'BSE_CAPITAL_GOODS',
            'index_name' => 'S&P BSE Capital Goods',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Capital Goods',
            'aliases' => ['BSE CAPITAL GOODS', 'S&P BSE CAPITAL GOODS'],
        ],
        [
            'index_code' => 'BSE_CONSUMER_DURABLES',
            'index_name' => 'S&P BSE Consumer Durables',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE Consumer Durables',
            'aliases' => ['BSE CONSUMER DURABLES', 'S&P BSE CONSUMER DURABLES'],
        ],
        [
            'index_code' => 'BSE_TECK',
            'index_name' => 'S&P BSE TECK',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE TECK',
            'aliases' => ['BSE TECK', 'S&P BSE TECK'],
        ],
        [
            'index_code' => 'BSE_PSU',
            'index_name' => 'S&P BSE PSU',
            'exchange' => 'BSE',
            'category' => 'Sectoral',
            'upstox_instrument_key' => 'BSE_INDEX|BSE PSU',
            'aliases' => ['BSE PSU', 'S&P BSE PSU'],
        ],
    ];

    /** @return array<string, int> */
    public function syncFromUrl(bool $dryRun = false): array
    {
        $url = trim((string) config('market_data.upstox.instruments_url'));
        if ($url === '') {
            throw new RuntimeException('The Upstox instruments URL is not configured.');
        }

        $response = Http::retry(4, 1000, null, false)
            ->acceptJson()
            ->withOptions(['verify' => TlsCaBundle::resolve(config('market_data.ca_bundle'))])
            ->connectTimeout(15)
            ->timeout(180)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('The Upstox instrument file download returned HTTP '.$response->status().'.');
        }

        $body = $response->body();
        $path = tempnam(sys_get_temp_dir(), 'upstox-instruments-');
        if ($path === false || file_put_contents($path, $body) === false) {
            throw new RuntimeException('A temporary Upstox instrument file could not be created.');
        }

        try {
            return $this->sync($path, $dryRun);
        } finally {
            @unlink($path);
        }
    }

    /**
     * Sync the NSE/BSE equity segments from an Upstox complete.json file.
     *
     * @return array<string, int>
     */
    public function sync(string $path, bool $dryRun = false): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new InvalidArgumentException("Upstox instrument file is not readable: {$path}");
        }

        $stats = [
            'scanned' => 0,
            'eligible_rows' => 0,
            'invalid_rows' => 0,
            'unique_isins' => 0,
            'unique_indices' => 0,
            'existing' => 0,
            'unmatched_existing' => 0,
            'added' => 0,
            'updated' => 0,
        ];
        $instruments = [];
        $indices = [];

        foreach ($this->readJsonArray($path) as $row) {
            $stats['scanned']++;
            $segment = strtoupper(trim((string) ($row['segment'] ?? '')));

            if (isset(self::INDEX_SEGMENTS[$segment])
                && strtoupper(trim((string) ($row['instrument_type'] ?? ''))) === 'INDEX') {
                $instrumentKey = trim((string) ($row['instrument_key'] ?? ''));
                $name = trim((string) ($row['name'] ?? $row['trading_symbol'] ?? ''));
                if ($name === '' || !str_starts_with($instrumentKey, $segment.'|')) {
                    $stats['invalid_rows']++;
                    continue;
                }

                $code = $this->indexCode($name, self::INDEX_SEGMENTS[$segment]);
                $indices[$code] = [
                    'index_code' => $code,
                    'index_name' => mb_substr($name, 0, 255),
                    'exchange' => self::INDEX_SEGMENTS[$segment],
                    'upstox_instrument_key' => $instrumentKey,
                    'upstox_metadata' => json_encode($row, JSON_THROW_ON_ERROR),
                ];
                $stats['eligible_rows']++;
                continue;
            }

            if (!isset(self::SEGMENTS[$segment]) || empty($row['isin'])) {
                continue;
            }

            $isin = strtoupper(trim((string) $row['isin']));
            $instrumentKey = trim((string) ($row['instrument_key'] ?? ''));

            if (!preg_match('/^[A-Z]{2}[A-Z0-9]{9}[0-9]$/', $isin)
                || !preg_match('/^'.preg_quote($segment, '/').'\|[A-Za-z0-9_ -]+$/', $instrumentKey)) {
                $stats['invalid_rows']++;
                continue;
            }

            $stats['eligible_rows']++;
            $exchange = self::SEGMENTS[$segment];
            $candidate = [
                'name' => $this->nullableString($row['name'] ?? null, 255),
                'short_name' => $this->nullableString($row['short_name'] ?? null, 255),
                'symbol' => $this->nullableString($row['trading_symbol'] ?? null, 255),
                'instrument_key' => $instrumentKey,
                'exchange_token' => $this->nullableString($row['exchange_token'] ?? null, 100),
                'series' => $this->nullableString($row['instrument_type'] ?? null, 10),
                'market_lot' => isset($row['lot_size']) && is_numeric($row['lot_size'])
                    ? max(1, (int) $row['lot_size'])
                    : null,
                'security_type' => $this->nullableString($row['security_type'] ?? null, 50),
                'tick_size' => $this->numericOrNull($row['tick_size'] ?? null),
                'freeze_quantity' => $this->numericOrNull($row['freeze_quantity'] ?? null),
                'qty_multiplier' => $this->numericOrNull($row['qty_multiplier'] ?? null),
                'mtf_enabled' => isset($row['mtf_enabled']) ? (bool) $row['mtf_enabled'] : null,
                'mtf_bracket' => $this->numericOrNull($row['mtf_bracket'] ?? null),
                'cas_eligible' => isset($row['cas_eligible']) ? (bool) $row['cas_eligible'] : null,
                'intraday_margin' => $this->numericOrNull($row['intraday_margin'] ?? null),
                'intraday_leverage' => $this->numericOrNull($row['intraday_leverage'] ?? null),
            ];

            if (!isset($instruments[$isin])) {
                $instruments[$isin] = ['nse' => null, 'bse' => null];
            }

            $instruments[$isin][$exchange] = $this->preferredCandidate(
                $instruments[$isin][$exchange],
                $candidate
            );
        }

        $stats['unique_isins'] = count($instruments);
        $stats['unique_indices'] = count($indices);
        $totalExisting = DB::table('equities')->count();
        $existingRows = DB::table('equities')
            ->whereIn('isin', array_keys($instruments))
            ->select([
                'isin', 'company_name', 'short_name', 'security_type',
                'nse_symbol', 'bse_symbol', 'series', 'market_lot',
                'qty_multiplier', 'mtf_enabled', 'mtf_bracket', 'cas_eligible',
                'intraday_margin', 'intraday_leverage',
            ])
            ->get()
            ->keyBy('isin');
        $stats['existing'] = $existingRows->count();
        $stats['unmatched_existing'] = $totalExisting - $stats['existing'];
        $stats['updated'] = $stats['existing'];
        $stats['added'] = $stats['unique_isins'] - $stats['existing'];

        if ($dryRun) {
            return $stats;
        }

        $now = now();
        $rows = [];

        foreach ($instruments as $isin => $exchanges) {
            $nse = $exchanges['nse'];
            $bse = $exchanges['bse'];
            $preferred = $nse ?? $bse;
            $existing = $existingRows->get($isin);

            $rows[] = [
                'isin' => $isin,
                'company_name' => $preferred['name'] ?? $existing?->company_name,
                'short_name' => $preferred['short_name'] ?? $existing?->short_name,
                'security_type' => $preferred['security_type'] ?? $existing?->security_type,
                'nse_symbol' => $nse['symbol'] ?? $existing?->nse_symbol,
                'bse_symbol' => $bse['symbol'] ?? $existing?->bse_symbol,
                'upstox_nse_instrument_key' => $nse['instrument_key'] ?? null,
                'upstox_bse_instrument_key' => $bse['instrument_key'] ?? null,
                'nse_exchange_token' => $nse['exchange_token'] ?? null,
                'bse_exchange_token' => $bse['exchange_token'] ?? null,
                'nse_tick_size' => $nse['tick_size'] ?? null,
                'bse_tick_size' => $bse['tick_size'] ?? null,
                'nse_freeze_quantity' => $nse['freeze_quantity'] ?? null,
                'bse_freeze_quantity' => $bse['freeze_quantity'] ?? null,
                'series' => $preferred['series'] ?? $existing?->series,
                'market_lot' => $preferred['market_lot'] ?? $existing?->market_lot,
                'qty_multiplier' => $preferred['qty_multiplier'] ?? $existing?->qty_multiplier,
                'mtf_enabled' => $preferred['mtf_enabled'] ?? $existing?->mtf_enabled,
                'mtf_bracket' => $preferred['mtf_bracket'] ?? $existing?->mtf_bracket,
                'cas_eligible' => $preferred['cas_eligible'] ?? $existing?->cas_eligible,
                'intraday_margin' => $preferred['intraday_margin'] ?? $existing?->intraday_margin,
                'intraday_leverage' => $preferred['intraday_leverage'] ?? $existing?->intraday_leverage,
                'upstox_nse_metadata' => $nse ? json_encode($nse, JSON_THROW_ON_ERROR) : null,
                'upstox_bse_metadata' => $bse ? json_encode($bse, JSON_THROW_ON_ERROR) : null,
                'upstox_synced_at' => $now,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $chunkSize = DB::connection()->getDriverName() === 'sqlite' ? 50 : 500;
        DB::transaction(function () use ($rows, $chunkSize): void {
            foreach (array_chunk($rows, $chunkSize) as $chunk) {
                DB::table('equities')->upsert(
                    $chunk,
                    ['isin'],
                    [
                        'company_name',
                        'short_name',
                        'security_type',
                        'nse_symbol',
                        'bse_symbol',
                        'upstox_nse_instrument_key',
                        'upstox_bse_instrument_key',
                        'nse_exchange_token',
                        'bse_exchange_token',
                        'nse_tick_size',
                        'bse_tick_size',
                        'nse_freeze_quantity',
                        'bse_freeze_quantity',
                        'series',
                        'market_lot',
                        'qty_multiplier',
                        'mtf_enabled',
                        'mtf_bracket',
                        'cas_eligible',
                        'intraday_margin',
                        'intraday_leverage',
                        'upstox_nse_metadata',
                        'upstox_bse_metadata',
                        'upstox_synced_at',
                        'is_active',
                        'updated_at',
                    ]
                );
            }
        });

        if ($indices !== []) {
            $existingByKey = DB::table('indices')
                ->whereIn('upstox_instrument_key', array_column($indices, 'upstox_instrument_key'))
                ->pluck('index_code', 'upstox_instrument_key')
                ->all();

            $indexRows = [];
            foreach ($indices as $code => $data) {
                $targetCode = $existingByKey[$data['upstox_instrument_key']] ?? $code;
                $data['index_code'] = $targetCode;
                $indexRows[$targetCode] = $data + [
                    'upstox_synced_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('indices')->upsert(
                array_values($indexRows),
                ['index_code'],
                ['index_name', 'exchange', 'upstox_instrument_key', 'upstox_metadata', 'upstox_synced_at', 'updated_at']
            );
        }

        return $stats;
    }

    /**
     * Read a top-level JSON array one object at a time without loading the
     * approximately 60 MB Upstox master into PHP memory.
     *
     * @return Generator<int, array<string, mixed>>
     */
    private function readJsonArray(string $path): Generator
    {
        $probe = fopen($path, 'rb');
        $magic = $probe === false ? false : fread($probe, 2);
        if ($probe !== false) {
            fclose($probe);
        }
        $gzip = $magic === "\x1f\x8b";
        $handle = $gzip ? gzopen($path, 'rb') : fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open Upstox instrument file: {$path}");
        }

        $object = '';
        $depth = 0;
        $inString = false;
        $escaped = false;
        $arrayStarted = false;

        try {
            while ($gzip ? !gzeof($handle) : !feof($handle)) {
                $chunk = $gzip ? gzread($handle, 65536) : fread($handle, 65536);
                if ($chunk === false) {
                    throw new RuntimeException('Unable to read the Upstox instrument file.');
                }

                $length = strlen($chunk);
                for ($index = 0; $index < $length; $index++) {
                    $character = $chunk[$index];

                    if (!$arrayStarted) {
                        if (ctype_space($character)) {
                            continue;
                        }
                        if ($character !== '[') {
                            throw new RuntimeException('Upstox instrument file must contain a JSON array.');
                        }
                        $arrayStarted = true;
                        continue;
                    }

                    if ($depth === 0) {
                        if ($character === '{') {
                            $object = '{';
                            $depth = 1;
                            $inString = false;
                            $escaped = false;
                        }
                        continue;
                    }

                    $object .= $character;

                    if ($inString) {
                        if ($escaped) {
                            $escaped = false;
                        } elseif ($character === '\\') {
                            $escaped = true;
                        } elseif ($character === '"') {
                            $inString = false;
                        }
                        continue;
                    }

                    if ($character === '"') {
                        $inString = true;
                    } elseif ($character === '{') {
                        $depth++;
                    } elseif ($character === '}') {
                        $depth--;
                        if ($depth === 0) {
                            try {
                                $decoded = json_decode($object, true, 512, JSON_THROW_ON_ERROR);
                            } catch (JsonException $exception) {
                                throw new RuntimeException('Invalid instrument JSON object: '.$exception->getMessage(), 0, $exception);
                            }
                            yield $decoded;
                            $object = '';
                        }
                    }
                }
            }
        } finally {
            $gzip ? gzclose($handle) : fclose($handle);
        }

        if (!$arrayStarted || $depth !== 0) {
            throw new RuntimeException('Upstox instrument file is incomplete or invalid.');
        }
    }

    /** @param array<string, mixed>|null $current @param array<string, mixed> $candidate */
    private function preferredCandidate(?array $current, array $candidate): array
    {
        if ($current === null) {
            return $candidate;
        }

        // Duplicate segment/ISIN rows share a key. Pick deterministically,
        // preferring the conventional NSE EQ series and then symbol order.
        $currentRank = $current['series'] === 'EQ' ? 0 : 1;
        $candidateRank = $candidate['series'] === 'EQ' ? 0 : 1;

        return [$candidateRank, $candidate['symbol'] ?? ''] < [$currentRank, $current['symbol'] ?? '']
            ? $candidate
            : $current;
    }

    private function nullableString(mixed $value, int $maxLength): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }

    private function numericOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function indexCode(string $name, string $exchange): string
    {
        if ($exchange === 'BSE') {
            $name = preg_replace('/^(S&P\s+)?BSE\s+/i', '', $name);
        }
        $code = trim(preg_replace('/_+/', '_', str_replace([' ', '&', '(', ')'], '_', strtoupper($name))), '_');

        return $exchange === 'BSE' ? 'BSE_'.$code : $code;
    }

    /**
     * Ensure all standard NSE and BSE indices have their Upstox instrument keys populated.
     * Maps existing indices without keys, and creates default tracking indices if missing.
     *
     * @return int Total number of indices configured with instrument keys
     */
    public function ensureIndexInstrumentKeys(): int
    {
        $existingRows = DB::table('indices')->get();
        $usedKeys = $existingRows
            ->whereNotNull('upstox_instrument_key')
            ->where('upstox_instrument_key', '<>', '')
            ->pluck('upstox_instrument_key')
            ->flip()
            ->all();

        $tokenMap = [];
        foreach (self::DEFAULT_INDICES as $def) {
            $tokenMap[$this->normalizeToken($def['index_code'])] = $def;
            $tokenMap[$this->normalizeToken($def['index_name'])] = $def;
            $tokenMap[$this->normalizeToken($def['upstox_instrument_key'])] = $def;
            foreach ($def['aliases'] ?? [] as $alias) {
                $tokenMap[$this->normalizeToken($alias)] = $def;
            }
        }

        $now = now();

        // 1. Update any existing index records that lack an instrument key
        foreach ($existingRows as $row) {
            $currentKey = trim((string) ($row->upstox_instrument_key ?? ''));
            if ($currentKey !== '') {
                continue;
            }

            $matchedDef = $tokenMap[$this->normalizeToken($row->index_code)]
                ?? $tokenMap[$this->normalizeToken($row->index_name)]
                ?? null;

            $candidateKey = $matchedDef['upstox_instrument_key'] ?? null;

            // Dynamic heuristic if not in default table:
            if (!$candidateKey) {
                $exchange = strtoupper(trim((string) ($row->exchange ?? 'NSE')));
                $cleanName = trim((string) $row->index_name);
                if ($exchange === 'BSE') {
                    $symbol = preg_replace('/^(S&P\s+)?BSE\s+/i', '', $cleanName);
                    $candidateKey = 'BSE_INDEX|' . ($symbol ?: $row->index_code);
                } else {
                    $candidateKey = 'NSE_INDEX|' . ($cleanName ?: $row->index_code);
                }
            }

            if ($candidateKey && !isset($usedKeys[$candidateKey])) {
                DB::table('indices')
                    ->where('index_code', $row->index_code)
                    ->update([
                        'upstox_instrument_key' => $candidateKey,
                        'category' => $row->category ?? ($matchedDef['category'] ?? null),
                        'updated_at' => $now,
                    ]);

                $usedKeys[$candidateKey] = true;
            }
        }

        // 2. Insert any default indices that do not exist at all (by code or instrument key)
        $existingCodes = DB::table('indices')->pluck('index_code')->flip()->all();
        $toInsert = [];

        foreach (self::DEFAULT_INDICES as $def) {
            $code = $def['index_code'];
            $key = $def['upstox_instrument_key'];

            if (isset($existingCodes[$code]) || isset($usedKeys[$key])) {
                continue;
            }

            $toInsert[] = [
                'index_code' => $code,
                'index_name' => $def['index_name'],
                'exchange' => $def['exchange'],
                'category' => $def['category'],
                'upstox_instrument_key' => $key,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $existingCodes[$code] = true;
            $usedKeys[$key] = true;
        }

        if ($toInsert !== []) {
            DB::table('indices')->insert($toInsert);
        }

        return DB::table('indices')
            ->whereNotNull('upstox_instrument_key')
            ->where('upstox_instrument_key', '<>', '')
            ->count();
    }

    private function normalizeToken(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-zA-Z0-9]/', '', $value));
    }
}
