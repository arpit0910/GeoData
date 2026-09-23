<?php

namespace App\Services;

use Generator;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use JsonException;
use RuntimeException;

class UpstoxInstrumentSyncService
{
    private const SEGMENTS = [
        'NSE_EQ' => 'nse',
        'BSE_EQ' => 'bse',
    ];

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
                'symbol' => $this->nullableString($row['trading_symbol'] ?? null, 255),
                'instrument_key' => $instrumentKey,
                'series' => $this->nullableString($row['instrument_type'] ?? null, 10),
                'market_lot' => isset($row['lot_size']) && is_numeric($row['lot_size'])
                    ? max(1, (int) $row['lot_size'])
                    : null,
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
        $existingIsins = DB::table('equities')
            ->whereIn('isin', array_keys($instruments))
            ->pluck('isin')
            ->flip();
        $stats['existing'] = $existingIsins->count();
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

            $rows[] = [
                'isin' => $isin,
                'company_name' => $preferred['name'],
                'nse_symbol' => $nse['symbol'] ?? null,
                'bse_symbol' => $bse['symbol'] ?? null,
                'upstox_nse_instrument_key' => $nse['instrument_key'] ?? null,
                'upstox_bse_instrument_key' => $bse['instrument_key'] ?? null,
                'series' => $preferred['series'],
                'market_lot' => $preferred['market_lot'],
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
                        'nse_symbol',
                        'bse_symbol',
                        'upstox_nse_instrument_key',
                        'upstox_bse_instrument_key',
                        'series',
                        'market_lot',
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
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException("Unable to open Upstox instrument file: {$path}");
        }

        $object = '';
        $depth = 0;
        $inString = false;
        $escaped = false;
        $arrayStarted = false;

        try {
            while (!feof($handle)) {
                $chunk = fread($handle, 65536);
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
            fclose($handle);
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
}
