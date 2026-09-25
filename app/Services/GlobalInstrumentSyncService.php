<?php

namespace App\Services;

use App\Models\GlobalInstrument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use JsonException;
use RuntimeException;

class GlobalInstrumentSyncService
{
    /** @return array{received: int, saved: int, deactivated: int} */
    public function sync(): array
    {
        $url = trim((string) config('market_data.upstox.global_instruments_url'));
        if ($url === '') {
            throw new RuntimeException('The global instruments URL is not configured.');
        }

        $response = Http::acceptJson()
            ->withOptions(['verify' => config('market_data.ca_bundle') ?: false])
            ->connectTimeout(15)
            ->timeout(120)
            ->get($url);

        if (!$response->successful()) {
            throw new RuntimeException('The global instruments file could not be downloaded (HTTP '.$response->status().').');
        }

        $body = $response->body();
        if (str_starts_with($body, "\x1f\x8b")) {
            $decoded = gzdecode($body);
            if ($decoded === false) {
                throw new RuntimeException('The global instruments gzip file could not be decoded.');
            }
            $body = $decoded;
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('The global instruments file contains invalid JSON.', 0, $exception);
        }

        $items = is_array($decoded['data'] ?? null) ? $decoded['data'] : $decoded;
        if (!is_array($items)) {
            throw new RuntimeException('The global instruments file has an unexpected structure.');
        }

        $now = now()->utc();
        $rows = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $instrumentKey = trim((string) ($item['instrument_key'] ?? ''));
            $segment = strtoupper(trim((string) ($item['segment'] ?? '')));
            if ($instrumentKey === '' || !in_array($segment, ['GLOBAL_INDEX', 'GLOBAL_INDICATOR'], true)) {
                continue;
            }

            $rows[$instrumentKey] = [
                'instrument_key' => $instrumentKey,
                'segment' => $segment,
                'name' => trim((string) ($item['name'] ?? $item['trading_symbol'] ?? $instrumentKey)),
                'exchange' => strtoupper(trim((string) ($item['exchange'] ?? 'GLOBAL'))) ?: 'GLOBAL',
                'country' => $this->nullableString($item['country'] ?? null),
                'latency' => $this->nullableString($item['latency'] ?? null),
                'instrument_type' => $this->nullableString($item['instrument_type'] ?? null),
                'trading_symbol' => $this->nullableString($item['trading_symbol'] ?? null),
                'start_time' => $this->nullableString($item['start_time'] ?? null),
                'end_time' => $this->nullableString($item['end_time'] ?? null),
                'week_days' => $this->nullableString($item['week_days'] ?? null),
                'is_active' => true,
                'provider_payload' => json_encode($item, JSON_THROW_ON_ERROR),
                'synced_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows === []) {
            throw new RuntimeException('No valid global instruments were found in the downloaded file.');
        }

        return DB::transaction(function () use ($rows): array {
            $keys = array_keys($rows);
            $deactivated = GlobalInstrument::query()
                ->where('is_active', true)
                ->whereNotIn('instrument_key', $keys)
                ->update(['is_active' => false, 'updated_at' => now()->utc()]);

            foreach (array_chunk(array_values($rows), 250) as $chunk) {
                GlobalInstrument::upsert(
                    $chunk,
                    ['instrument_key'],
                    [
                        'segment', 'name', 'exchange', 'country', 'latency', 'instrument_type',
                        'trading_symbol', 'start_time', 'end_time', 'week_days', 'is_active',
                        'provider_payload', 'synced_at', 'updated_at',
                    ]
                );
            }

            return [
                'received' => count($rows),
                'saved' => count($rows),
                'deactivated' => $deactivated,
            ];
        });
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
