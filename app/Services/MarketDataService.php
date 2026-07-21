<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MarketDataService
{
    /**
     * Yahoo Finance v8 chart endpoint used for spot-style quote snapshots.
     */
    private const BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart/';

    /**
     * Fetch a single symbol and cache the latest good payload forever.
     * If Yahoo fails, we fall back to the last cached state.
     */
    public function getQuote(string $symbol): array
    {
        $symbol = trim($symbol);
        $cacheKey = $this->cacheKey($symbol);

        try {
            $payload = $this->fetchLivePayload($symbol);

            Cache::forever($cacheKey, $payload);

            return $payload;
        } catch (Throwable $exception) {
            $cached = Cache::get($cacheKey);

            Log::warning('Yahoo Finance live quote fetch failed; falling back to cache.', [
                'symbol' => $symbol,
                'message' => $exception->getMessage(),
                'has_cached_value' => ! empty($cached),
            ]);

            if (! empty($cached)) {
                $cached['source'] = 'cache';

                return $cached;
            }

            return [
                'symbol' => $symbol,
                'price' => null,
                'previous_close' => null,
                'd' => null,
                'dp' => null,
                'currency' => null,
                'exchange' => null,
                'fetched_at' => now()->toIso8601String(),
                'source' => 'unavailable',
            ];
        }
    }

    /**
     * Fetch a batch of symbols and return a symbol-keyed payload map.
     */
    public function getQuotes(array $symbols): array
    {
        $quotes = [];

        foreach ($this->normalizeSymbols($symbols) as $symbol) {
            $quotes[$symbol] = $this->getQuote($symbol);
        }

        return $quotes;
    }

    /**
     * Normalize comma-separated or array based symbol lists.
     */
    public function normalizeSymbols(array|string|null $symbols, array $defaults = []): array
    {
        if (is_string($symbols)) {
            $symbols = explode(',', $symbols);
        }

        $symbols = collect($symbols ?? [])
            ->map(fn ($symbol) => strtoupper(trim((string) $symbol)))
            ->filter()
            ->values()
            ->all();

        if ($symbols === []) {
            return $defaults;
        }

        return array_values(array_unique($symbols));
    }

    private function transformResponse(string $symbol, array $response): array
    {
        $result = data_get($response, 'chart.result.0');
        $error = data_get($response, 'chart.error');

        if (! is_array($result) || $error) {
            throw new RuntimeException('Yahoo Finance returned an invalid chart payload.');
        }

        $meta = data_get($result, 'meta', []);
        $price = $this->toFloat(data_get($meta, 'regularMarketPrice'));
        $previousClose = $this->toFloat(
            data_get($meta, 'chartPreviousClose', data_get($meta, 'previousClose'))
        );

        if ($price === null) {
            throw new RuntimeException('regularMarketPrice missing from Yahoo Finance response.');
        }

        $change = null;
        $changePercent = null;

        if ($previousClose !== null && $previousClose != 0.0) {
            $change = round($price - $previousClose, 4);
            $changePercent = round(($change / $previousClose) * 100, 4);
        }

        return [
            'symbol' => $symbol,
            'price' => $price,
            'previous_close' => $previousClose,
            'd' => $change,
            'dp' => $changePercent,
            'currency' => data_get($meta, 'currency'),
            'exchange' => data_get($meta, 'exchangeName', data_get($meta, 'fullExchangeName')),
            'fetched_at' => now()->toIso8601String(),
            'source' => 'live',
        ];
    }

    /**
     * Prefer Laravel's HTTP client, but fall back to PHP streams when the
     * local cURL/OpenSSL bundle cannot validate Yahoo's certificate chain.
     */
    private function fetchLivePayload(string $symbol): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0',
                ])
                ->timeout(10)
                ->retry(1, 250)
                ->get(self::BASE_URL.rawurlencode($symbol), [
                    'interval' => '1d',
                    'range' => '1d',
                ])
                ->throw();

            return $this->transformResponse($symbol, $response->json());
        } catch (Throwable $exception) {
            Log::warning('Laravel HTTP client failed for Yahoo Finance, trying stream fallback.', [
                'symbol' => $symbol,
                'message' => $exception->getMessage(),
            ]);

            return $this->transformResponse($symbol, $this->fetchViaStream($symbol));
        }
    }

    private function fetchViaStream(string $symbol): array
    {
        $url = self::BASE_URL.rawurlencode($symbol).'?interval=1d&range=1d';
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 10,
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'User-Agent: Mozilla/5.0',
                ]),
            ],
        ]);

        $body = @file_get_contents($url, false, $context);

        if ($body === false) {
            throw new RuntimeException('Yahoo Finance stream fallback request failed.');
        }

        $decoded = json_decode($body, true);

        if (! is_array($decoded)) {
            throw new RuntimeException('Yahoo Finance stream fallback returned invalid JSON.');
        }

        return $decoded;
    }

    private function toFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return round((float) $value, 4);
    }

    private function cacheKey(string $symbol): string
    {
        return 'market-data:latest:'.md5(strtoupper($symbol));
    }
}
