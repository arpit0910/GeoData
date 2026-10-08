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
            'existing' => 0,
            'unmatched_existing' => 0,
            'added' => 0,
            'updated' => 0,
        ];
        $instruments = [];

        foreach ($this->readJsonArray($path) as $row) {
            $stats['scanned']++;
            $segment = strtoupper(trim((string) ($row['segment'] ?? '')));

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
}
