<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class UpstoxMarketDataService
{
    public const MAX_INSTRUMENTS = 500;
    public const MAX_NEWS_INSTRUMENTS = 30;

    /**
     * Fetch LTP snapshots for requested instrument keys.
     *
     * @param array<int, string> $instrumentKeys
     * @return array<string, array<string, mixed>> keyed by instrument key
     */
    public function ltp(array $instrumentKeys): array
    {
        $instrumentKeys = $this->cleanInstrumentKeys($instrumentKeys);

        if ($instrumentKeys === []) {
            return [];
        }
        if (count($instrumentKeys) > self::MAX_INSTRUMENTS) {
            throw new RuntimeException('Upstox accepts at most 500 instrument keys per LTP request.');
        }

        $token = $this->token();
        $verify = config('market_data.ca_bundle') ?: false;

        $response = Http::acceptJson()
            ->withToken($token)
            ->withOptions(['verify' => $verify])
            ->connectTimeout(10)
            ->timeout(45)
            ->get(config('market_data.upstox.ltp_url', 'https://api.upstox.com/v3/market-quote/ltp'), [
                'instrument_key' => implode(',', $instrumentKeys),
            ]);

        if (!$response->successful()) {
            throw new RuntimeException($this->errorMessage($response));
        }

        $body = $response->json();
        if (($body['status'] ?? null) !== 'success' || !is_array($body['data'] ?? null)) {
            throw new RuntimeException('Upstox returned an invalid LTP response.');
        }

        $quotes = [];

        foreach ($body['data'] as $key => $item) {
            if (!is_array($item)) {
                continue;
            }

            $instrumentKey = trim((string) ($item['instrument_token'] ?? ''));
            if ($instrumentKey === '') {
                // If instrument_token is not in item, check key format like "NSE_EQ:RELIANCE"
                $instrumentKey = str_replace(':', '|', $key);
            }

            $price = $this->number($item['last_price'] ?? null);
            if ($price === null) {
                continue;
            }

            $previousClose = $this->number($item['cp'] ?? null);
            $change = null;
            $changePercent = null;

            if ($previousClose !== null && $previousClose > 0) {
                $change = round($price - $previousClose, 4);
                $changePercent = round(($change / $previousClose) * 100, 4);
            }

            // Extract symbol from key e.g. "NSE_EQ:RELIANCE"
            $symbol = '';
            if (str_contains($key, ':')) {
                $parts = explode(':', $key, 2);
                $symbol = $parts[1] ?? '';
            }

            $quotes[$instrumentKey] = [
                'symbol' => $symbol,
                'price' => $price,
                'previous_close' => $previousClose,
                'd' => $change,
                'dp' => $changePercent,
                'volume' => $item['volume'] ?? null,
                'last_quantity' => $item['ltq'] ?? null,
                'currency' => 'INR',
                'exchange' => str_starts_with($instrumentKey, 'BSE_') ? 'BSE' : 'NSE',
                'fetched_at' => now()->utc()->toIso8601String(),
                'quoted_at' => now()->utc()->toIso8601String(),
                'source' => 'live',
                'provider' => 'upstox',
                'market_data' => $item,
            ];
        }

        return $quotes;
    }

    /**
     * Fetch a single full-quote snapshot for each requested instrument key.
     *
     * @param array<int, string> $instrumentKeys
     * @return array<string, array<string, mixed>> keyed by instrument key
     */
    public function quotes(array $instrumentKeys): array
    {
        $instrumentKeys = $this->cleanInstrumentKeys($instrumentKeys);

        if ($instrumentKeys === []) {
            return [];
        }
        if (count($instrumentKeys) > self::MAX_INSTRUMENTS) {
            throw new RuntimeException('Upstox accepts at most 500 instrument keys per quote request.');
        }

        $token = $this->token();
        $verify = config('market_data.ca_bundle') ?: false;

        $response = Http::acceptJson()
            ->withToken($token)
            ->withOptions(['verify' => $verify])
            ->connectTimeout(10)
            ->timeout(45)
            ->get(config('market_data.upstox.quote_url', 'https://api.upstox.com/v3/market-quote/quotes'), [
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

    /**
     * Fetch corporate actions (splits, bonuses, dividends, rights, etc.) for a company by ISIN.
     *
     * @return array<int, array<string, mixed>>
     */
    public function corporateActions(string $isin): array
    {
        $isin = strtoupper(trim($isin));
        if ($isin === '') {
            return [];
        }

        $token = $this->token();
        $verify = config('market_data.ca_bundle') ?: false;
        $baseUrl = rtrim(config('market_data.upstox.corporate_actions_url', 'https://api.upstox.com/v2/fundamentals'), '/');
        $url = "{$baseUrl}/{$isin}/corporate-actions";

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withOptions(['verify' => $verify])
                ->connectTimeout(10)
                ->timeout(30)
                ->get($url);

            if ($response->status() === 404) {
                return [];
            }

            if (!$response->successful()) {
                throw new RuntimeException($this->errorMessage($response));
            }

            $body = $response->json();
            if (($body['status'] ?? null) !== 'success' || !is_array($body['data'] ?? null)) {
                return [];
            }

            $actions = [];
            foreach ($body['data'] as $item) {
                if (!is_array($item)) {
                    continue;
                }

                $name = trim((string) ($item['name'] ?? 'Corporate Action'));
                $normalizedType = $this->determineCorporateActionType($name, $item);

                $eventDetails = [];
                if (isset($item['event_details']) && is_array($item['event_details'])) {
                    foreach ($item['event_details'] as $detail) {
                        if (isset($detail['name']) && isset($detail['value'])) {
                            $eventDetails[trim((string) $detail['name'])] = trim((string) $detail['value']);
                        }
                    }
                }

                $announcementDate = $this->parseDate($eventDetails['Announcement date'] ?? null);
                $exDate = $this->parseDate($eventDetails['Ex dividend date'] ?? $eventDetails['Ex-date'] ?? $item['expiry_date'] ?? null);
                $recordDate = $this->parseDate($eventDetails['Record date'] ?? null);
                $amount = $this->number($item['amount'] ?? $eventDetails['Amount'] ?? null);
                $ratio = $item['ratio'] ?? $eventDetails['Ratio'] ?? null;
                $details = $eventDetails['Details'] ?? null;

                $actions[] = [
                    'isin' => $isin,
                    'name' => $name,
                    'type' => $normalizedType,
                    'expiry_date' => $exDate,
                    'record_date' => $recordDate,
                    'announcement_date' => $announcementDate,
                    'amount' => $amount,
                    'ratio' => $ratio ? trim((string) $ratio) : null,
                    'details' => $details ?: ($ratio ? "Ratio: {$ratio}" : ($amount ? "Amount: ₹{$amount}" : $name)),
                    'raw_data' => $item,
                ];
            }

            return $actions;
        } catch (Throwable $e) {
            report($e);
            return [];
        }
    }

    /**
     * Fetch market and stock news for given instrument keys.
     *
     * @param array<int, string> $instrumentKeys
     * @return array<int, array<string, mixed>>
     */
    public function news(array $instrumentKeys): array
    {
        $instrumentKeys = array_slice($this->cleanInstrumentKeys($instrumentKeys), 0, self::MAX_NEWS_INSTRUMENTS);
        if ($instrumentKeys === []) {
            return [];
        }

        $token = $this->token();
        $verify = config('market_data.ca_bundle') ?: false;
        $url = config('market_data.upstox.news_url', 'https://api.upstox.com/v2/news');

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->withOptions(['verify' => $verify])
                ->connectTimeout(10)
                ->timeout(30)
                ->get($url, [
                    'category' => 'instrument_keys',
                    'instrument_keys' => implode(',', $instrumentKeys),
                ]);

            if (!$response->successful()) {
                throw new RuntimeException($this->errorMessage($response));
            }

            $body = $response->json();
            if (($body['status'] ?? null) !== 'success' || !is_array($body['data'] ?? null)) {
                return [];
            }

            $articles = [];
            foreach ($body['data'] as $instrumentKey => $items) {
                if (!is_array($items)) {
                    continue;
                }

                // Extract ISIN from instrumentKey (e.g. "NSE_EQ|INE002A01018")
                $isin = null;
                if (str_contains($instrumentKey, '|')) {
                    $isin = explode('|', $instrumentKey, 2)[1] ?? null;
                }

                foreach ($items as $item) {
                    if (!is_array($item) || empty($item['heading'])) {
                        continue;
                    }

                    $publishedAt = null;
                    if (!empty($item['published_time'])) {
                        $publishedAt = Carbon::createFromTimestampMsUTC((int) $item['published_time'])->toDateTimeString();
                    }

                    $articles[] = [
                        'isin' => $isin,
                        'instrument_key' => $instrumentKey,
                        'title' => trim((string) $item['heading']),
                        'summary' => isset($item['summary']) ? trim((string) $item['summary']) : null,
                        'thumbnail' => isset($item['thumbnail']) ? trim((string) $item['thumbnail']) : null,
                        'article_url' => isset($item['article_link']) ? trim((string) $item['article_link']) : null,
                        'source' => 'Upstox',
                        'published_at' => $publishedAt,
                        'raw_data' => $item,
                    ];
                }
            }

            return $articles;
        } catch (Throwable $e) {
            report($e);
            return [];
        }
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

    private function determineCorporateActionType(string $name, array $item): string
    {
        $upper = strtoupper($name);
        if (str_contains($upper, 'SPLIT')) {
            return 'SPLIT';
        }
        if (str_contains($upper, 'BONUS')) {
            return 'BONUS';
        }
        if (str_contains($upper, 'DIVIDEND')) {
            return 'DIVIDEND';
        }
        if (str_contains($upper, 'RIGHTS')) {
            return 'RIGHTS';
        }
        return 'EVENT';
    }

    private function parseDate(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (Throwable) {
            return null;
        }
    }

    private function token(): string
    {
        $token = trim((string) config('market_data.upstox.access_token'));
        if ($token === '') {
            throw new RuntimeException('UPSTOX_ACCESS_TOKEN or UPSTOX_TOKEN is not configured.');
        }
        return $token;
    }

    /**
     * @param array<int, string> $keys
     * @return array<int, string>
     */
    private function cleanInstrumentKeys(array $keys): array
    {
        return array_values(array_unique(array_filter(array_map(
            fn ($key) => trim((string) $key),
            $keys
        ))));
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

        return 'Upstox request failed with HTTP '.$response->status().
            ($message ? ': '.$message : '.');
    }
}
