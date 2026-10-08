<?php

namespace App\Services;

use App\Support\TlsCaBundle;
use Carbon\CarbonImmutable;
use Generator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;
use Throwable;

class UpstoxMutualFundNavSyncService
{
    /**
     * Import the latest published NAVs from Upstox's daily MF instrument file.
     *
     * @return array{received: int, matched: int, saved: int, unmatched: int, latest_date: string|null}
     */
    public function sync(bool $dryRun = false): array
    {
        $url = trim((string) config('market_data.upstox.mf_instruments_url'));
        if ($url === '') {
            throw new RuntimeException('The Upstox mutual-fund instruments URL is not configured.');
        }

        $response = Http::retry(4, 1000, null, false)
            ->acceptJson()
            ->withOptions(['verify' => TlsCaBundle::resolve(config('market_data.ca_bundle'))])
            ->connectTimeout(15)
            ->timeout(120)
            ->get($url);

        if (! $response->successful()) {
            throw new RuntimeException('Upstox mutual-fund instruments download returned HTTP '.$response->status().'.');
        }

        $fallbackDate = $this->dateFromFileHeader($response->header('Last-Modified'));
        $body = $response->body();
        $gzip = str_starts_with($body, "\x1f\x8b");
        $path = tempnam(sys_get_temp_dir(), 'upstox-mf-');
        if ($path === false || file_put_contents($path, $body) === false) {
            throw new RuntimeException('A temporary Upstox mutual-fund file could not be created.');
        }
        unset($response, $body);

        $funds = DB::table('mutual_funds')
            ->select('id', 'isin', 'isin_reinvest')
            ->get();
        $byIsin = [];
        foreach ($funds as $fund) {
            $byIsin[strtoupper((string) $fund->isin)] = $fund;
            if ($fund->isin_reinvest) {
                $byIsin[strtoupper((string) $fund->isin_reinvest)] = $fund;
            }
        }

        $received = 0;
        $unmatched = 0;
        $rows = [];
        $latestDate = null;
        $oldestAcceptedDate = CarbonImmutable::today('Asia/Kolkata')->subDays(10);
        $today = CarbonImmutable::today('Asia/Kolkata');

        try {
            foreach ($this->readJsonArray($path, $gzip) as $item) {

                $received++;
                $isin = strtoupper(trim((string) ($item['isin'] ?? $item['instrument_key'] ?? '')));
                $nav = $item['last_price'] ?? null;
                $dateValue = trim((string) ($item['last_price_date'] ?? $fallbackDate));
                $fund = $byIsin[$isin] ?? null;

                try {
                    $date = CarbonImmutable::createFromFormat('Y-m-d', $dateValue, 'Asia/Kolkata');
                    $validDate = $date && $date->format('Y-m-d') === $dateValue;
                    if ($validDate) {
                        $date = $date->startOfDay();
                    }
                } catch (Throwable) {
                    $validDate = false;
                    $date = null;
                }

                if (! $fund || ! is_numeric($nav) || (float) $nav <= 0 || ! $validDate
                    || $date->lt($oldestAcceptedDate) || $date->gt($today)) {
                    $unmatched++;
                    continue;
                }

                $canonicalIsin = strtoupper((string) $fund->isin);
                $rows[$canonicalIsin.'|'.$dateValue] = [
                    'isin' => $canonicalIsin,
                    'mf_id' => (int) $fund->id,
                    'nav_date' => $dateValue,
                    'nav' => round((float) $nav, 4),
                ];
                $latestDate = $latestDate === null || $dateValue > $latestDate ? $dateValue : $latestDate;
            }
        } finally {
            @unlink($path);
        }

        if ($rows === []) {
            throw new RuntimeException('Upstox did not provide any recent NAVs matching the mutual-fund master.');
        }

        if (! $dryRun) {
            foreach (array_chunk(array_values($rows), 1000) as $chunk) {
                DB::table('mutual_fund_prices')->upsert(
                    $chunk,
                    ['isin', 'nav_date'],
                    ['mf_id', 'nav']
                );
            }
        }

        return [
            'received' => $received,
            'matched' => count($rows),
            'saved' => $dryRun ? 0 : count($rows),
            'unmatched' => $unmatched,
            'latest_date' => $latestDate,
        ];
    }

    /** @return Generator<int, array<string, mixed>> */
    private function readJsonArray(string $path, bool $gzip): Generator
    {
        $handle = $gzip ? gzopen($path, 'rb') : fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('The Upstox mutual-fund instruments file could not be opened.');
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
                    throw new RuntimeException('The Upstox mutual-fund instruments file could not be read.');
                }

                for ($index = 0, $length = strlen($chunk); $index < $length; $index++) {
                    $character = $chunk[$index];

                    if (!$arrayStarted) {
                        if (ctype_space($character)) {
                            continue;
                        }
                        if ($character !== '[') {
                            throw new RuntimeException('The Upstox mutual-fund instruments file must contain a JSON array.');
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
                                yield json_decode($object, true, 512, JSON_THROW_ON_ERROR);
                            } catch (JsonException $exception) {
                                throw new RuntimeException('The Upstox mutual-fund instruments file contains invalid JSON.', 0, $exception);
                            }
                            $object = '';
                        }
                    }
                }
            }
        } finally {
            $gzip ? gzclose($handle) : fclose($handle);
        }

        if (!$arrayStarted || $depth !== 0) {
            throw new RuntimeException('The Upstox mutual-fund instruments file is incomplete or invalid.');
        }
    }

    private function dateFromFileHeader(?string $lastModified): string
    {
        try {
            $date = $lastModified
                ? CarbonImmutable::parse($lastModified)->timezone('Asia/Kolkata')->startOfDay()->subDay()
                : CarbonImmutable::today('Asia/Kolkata');
        } catch (Throwable) {
            $date = CarbonImmutable::today('Asia/Kolkata');
        }

        while ($date->isWeekend()) {
            $date = $date->subDay();
        }

        return $date->toDateString();
    }
}
