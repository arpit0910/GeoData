<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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
     * Yahoo's spark endpoint accepts multiple symbols in one request. This is
     * essential for the equity master, which contains several thousand symbols.
     */
    private const BATCH_URL = 'https://query1.finance.yahoo.com/v7/finance/spark';
    private const BATCH_SIZE = 20;

    /**
     * Fetch a single symbol and cache the latest good payload forever.
     * If Yahoo fails, we fall back to the last cached state.
     */
    public function getQuote(string $symbol): array
    {
        $symbol = strtoupper(trim($symbol));
        $cacheKey = $this->cacheKey($symbol);

        try {
            $payload = $this->fetchLivePayload($symbol);

            $this->storeQuoteCache($cacheKey, $payload);

            return $payload;
        } catch (Throwable $exception) {
            $cached = $this->readQuoteCache($cacheKey);

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

        foreach (array_chunk($this->normalizeSymbols($symbols), self::BATCH_SIZE) as $batch) {
            try {
                $response = Http::acceptJson()
                    ->withOptions(['verify' => config('market_data.ca_bundle') ?: true])
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
                    ->connectTimeout(5)
                    ->timeout(15)
                    ->get(self::BATCH_URL, [
                        'symbols' => implode(',', $batch),
                        'interval' => '1d',
                        'range' => '1d',
                    ])
                    ->throw()
                    ->json();

                $results = collect(data_get($response, 'spark.result', []))
                    ->filter(fn ($result) => is_array($result) && ! empty($result['symbol']))
                    ->keyBy(fn ($result) => strtoupper((string) $result['symbol']));

                foreach ($batch as $symbol) {
                    $result = $results->get($symbol);
                    $chart = is_array($result) ? data_get($result, 'response.0') : null;

                    if (! is_array($chart)) {
                        $quotes[$symbol] = $this->fallbackPayload(
                            $symbol,
                            new RuntimeException('Symbol missing from Yahoo Finance batch response.')
                        );
                        continue;
                    }

                    try {
                        $payload = $this->transformResponse($symbol, [
                            'chart' => ['result' => [$chart], 'error' => null],
                        ]);
                        $this->storeQuoteCache($this->cacheKey($symbol), $payload);
                        $quotes[$symbol] = $payload;
                    } catch (Throwable $exception) {
                        $quotes[$symbol] = $this->fallbackPayload($symbol, $exception);
                    }
                }
            } catch (Throwable $exception) {
                foreach ($batch as $symbol) {
                    $quotes[$symbol] = $this->fallbackPayload($symbol, $exception);
                }
            }
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
            'quoted_at' => is_numeric(data_get($meta, 'regularMarketTime'))
                ? \Carbon\Carbon::createFromTimestampUTC((int) $meta['regularMarketTime'])->toIso8601String()
                : null,
            'source' => 'live',
        ];
    }

    private function fetchLivePayload(string $symbol): array
    {
        $response = Http::acceptJson()
            ->withOptions(['verify' => config('market_data.ca_bundle') ?: true])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->connectTimeout(5)
            ->timeout(10)
            ->get(self::BASE_URL.rawurlencode($symbol), ['interval' => '1d', 'range' => '1d'])
            ->throw();

        return $this->transformResponse($symbol, $response->json());
    }

    private function toFloat(mixed $value): ?float
    {
        if (! is_numeric($value) || ! is_finite((float) $value)) {
            return null;
        }

        return round((float) $value, 4);
    }

    private function cacheKey(string $symbol): string
    {
        return 'market-data:latest:'.md5(strtoupper($symbol));
    }

    private function storeQuoteCache(string $cacheKey, array $payload): void
    {
        try {
            Cache::forever($cacheKey, $payload);
        } catch (Throwable $exception) {
            try {
                DB::reconnect();
                Cache::forever($cacheKey, $payload);
                return;
            } catch (Throwable $retryException) {
                Log::warning('Unable to persist market quote cache.', [
                    'cache_key' => $cacheKey,
                    'message' => $retryException->getMessage(),
                ]);
            }
        }
    }

    private function readQuoteCache(string $cacheKey): mixed
    {
        try {
            return Cache::get($cacheKey);
        } catch (Throwable $exception) {
            try {
                DB::reconnect();
                return Cache::get($cacheKey);
            } catch (Throwable $retryException) {
                Log::warning('Unable to read market quote cache.', [
                    'cache_key' => $cacheKey,
                    'message' => $retryException->getMessage(),
                ]);

                return null;
            }
        }
    }

    private function fallbackPayload(string $symbol, Throwable $exception): array
    {
        $cached = $this->readQuoteCache($this->cacheKey($symbol));

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
