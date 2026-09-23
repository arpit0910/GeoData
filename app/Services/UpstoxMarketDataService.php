<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class UpstoxMarketDataService
{
    public const MAX_INSTRUMENTS = 500;

    /**
     * Fetch a single full-quote snapshot for each requested instrument key.
     *
     * @param array<int, string> $instrumentKeys
     * @return array<string, array<string, mixed>> keyed by instrument key
     */
    public function quotes(array $instrumentKeys): array
    {
        $instrumentKeys = array_values(array_unique(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $instrumentKeys
        ))));

        if ($instrumentKeys === []) {
            return [];
        }
        if (count($instrumentKeys) > self::MAX_INSTRUMENTS) {
            throw new RuntimeException('Upstox accepts at most 500 instrument keys per quote request.');
        }

        $token = trim((string) config('market_data.upstox.access_token'));
        if ($token === '') {
            throw new RuntimeException('UPSTOX_ACCESS_TOKEN is not configured.');
        }

        $response = Http::acceptJson()
            ->withToken($token)
            ->withOptions(['verify' => config('market_data.ca_bundle') ?: true])
            ->connectTimeout(10)
            ->timeout(45)
            ->get(config('market_data.upstox.quote_url'), [
                'instrument_key' => implode(',', $instrumentKeys),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $body = $response->json();
        if (($body['status'] ?? null) !== 'success' || !is_array($body['data'] ?? null)) {
            throw new RuntimeException('Upstox returned an invalid full-quote response.');
        }

        $requested = array_fill_keys($instrumentKeys, true);
        $quotes = [];

        foreach ($body['data'] as $providerQuote) {
            if (!is_array($providerQuote)) {
                continue;
            }

            $instrumentKey = trim((string) ($providerQuote['instrument_token'] ?? ''));
            if ($instrumentKey === '' || !isset($requested[$instrumentKey])) {
                continue;
            }

            $quote = $this->normalizeQuote($instrumentKey, $providerQuote);
            if ($quote !== null) {
                $quotes[$instrumentKey] = $quote;
            }
        }

        return $quotes;
    }

    /** @param array<string, mixed> $providerQuote */
    private function normalizeQuote(string $instrumentKey, array $providerQuote): ?array
    {
        $price = $this->number($providerQuote['last_price'] ?? null);
        if ($price === null) {
            return null;
        }

        $previousClose = $this->number($providerQuote['prev_close_price'] ?? data_get($providerQuote, 'ohlc.close'));
        $change = $this->number($providerQuote['net_change'] ?? null);
        if ($change === null && $previousClose !== null) {
            $change = round($price - $previousClose, 4);
        }
        $changePercent = $previousClose !== null && $previousClose != 0.0 && $change !== null
            ? round(($change / $previousClose) * 100, 4)
            : null;

        // Never persist the provider key in the public quote payload.
        unset($providerQuote['instrument_token']);

        return [
            'symbol' => trim((string) ($providerQuote['symbol'] ?? '')),
            'price' => $price,
            'previous_close' => $previousClose,
            'd' => $change,
            'dp' => $changePercent,
            'currency' => 'INR',
            'exchange' => str_starts_with($instrumentKey, 'BSE_') ? 'BSE' : 'NSE',
            'fetched_at' => now()->utc()->toIso8601String(),
            'quoted_at' => $this->quoteTime($providerQuote),
            'source' => 'live',
            'provider' => 'upstox',
            'market_data' => $providerQuote,
        ];
    }

    /** @param array<string, mixed> $quote */
    private function quoteTime(array $quote): string
    {
        $lastTradeTime = $quote['last_trade_time'] ?? null;
        if (is_numeric($lastTradeTime)) {
            return Carbon::createFromTimestampMsUTC((int) $lastTradeTime)->toIso8601String();
        }

        if (!empty($quote['timestamp'])) {
            return Carbon::parse($quote['timestamp'])->utc()->toIso8601String();
        }

        return now()->utc()->toIso8601String();
    }

    private function number(mixed $value): ?float
    {
        return is_numeric($value) && is_finite((float) $value)
            ? round((float) $value, 4)
            : null;
    }

    private function errorMessage(Response $response): string
    {
        $message = data_get($response->json(), 'errors.0.message')
            ?? data_get($response->json(), 'message');

        return 'Upstox quote request failed with HTTP '.$response->status().
            ($message ? ': '.$message : '.');
    }
}
