<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Models\Index as MarketIndex;
use App\Models\MarketNews;
use App\Services\UpstoxMarketDataService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class SyncUpstoxNewsCommand extends Command
{
    protected $signature = 'market:sync-upstox-news
        {--batch-size=30 : Instrument keys per Upstox news request (max 30)}
        {--max-batches= : Maximum batches in a normal rotating run}
        {--limit= : Optional maximum instruments to query; bounded rotating coverage when omitted}
        {--all : Scan every eligible equity and index in one run}
        {--isin=* : Specific ISINs to fetch news for}
        {--reset-cursor : Restart the rotating scan from the first eligible equity}';

    protected $description = 'Sync latest market and stock news from Upstox API';

    public function handle(UpstoxMarketDataService $upstox): int
    {
        $batchSize = min(30, max(1, (int) $this->option('batch-size')));
        $maxBatches = min(100, max(1, (int) ($this->option('max-batches')
            ?: config('market_data.upstox.news_max_batches', 20))));
        $limit = $this->option('limit') !== null
            ? max(1, (int) $this->option('limit'))
            : null;

        $query = Equity::query()
            ->where('is_active', true)
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->whereNotNull('upstox_nse_instrument_key')
                        ->where('upstox_nse_instrument_key', '<>', '');
                })->orWhere(function ($query) {
                    $query->whereNotNull('upstox_bse_instrument_key')
                        ->where('upstox_bse_instrument_key', '<>', '');
                });
            });

        $isinFilter = collect($this->option('isin'))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))
            ->filter()
            ->unique();

        if ($isinFilter->isNotEmpty()) {
            $query->whereIn('isin', $isinFilter->all());
        }

        $indexInstruments = $isinFilter->isEmpty()
            ? MarketIndex::query()
                ->whereNotNull('upstox_instrument_key')
                ->where('upstox_instrument_key', '<>', '')
                ->orderBy('index_code')
                ->get(['index_code', 'upstox_instrument_key'])
                ->map(fn ($index) => (object) [
                    'id' => null,
                    'isin' => null,
                    'nse_symbol' => $index->index_code,
                    'bse_symbol' => null,
                    'upstox_nse_instrument_key' => $index->upstox_instrument_key,
                    'upstox_bse_instrument_key' => null,
                ])
            : collect();
        $totalEligible = (clone $query)->count() + $indexInstruments->count();
        $cursorKey = 'market_news_sync:last_equity_id';
        $retryKey = 'market_news_sync:retry_equity_ids';
        $syncAll = (bool) $this->option('all');
        $usesCursor = ! $syncAll && $isinFilter->isEmpty() && $limit === null;
        if ($this->option('reset-cursor')) {
            Cache::forget($cursorKey);
            Cache::forget($retryKey);
        }
        $cursor = $usesCursor ? max(0, (int) Cache::get($cursorKey, 0)) : 0;
        $selectionLimit = $syncAll ? $totalEligible : ($limit ?? ($batchSize * $maxBatches));
        $columns = [
            'id', 'isin', 'nse_symbol', 'bse_symbol',
            'upstox_nse_instrument_key', 'upstox_bse_instrument_key',
        ];
        $retryIds = $usesCursor
            ? collect(Cache::get($retryKey, []))->map(fn ($id) => (int) $id)->filter()->unique()->values()
            : collect();
        $retryEquities = $retryIds->isEmpty()
            ? collect()
            : (clone $query)->whereIn('id', $retryIds)->orderBy('id')->limit($selectionLimit)->get($columns);
        $retryIds = $retryEquities->pluck('id')->values();
        $remaining = max(0, $selectionLimit - $retryEquities->count());
        $selection = (clone $query)
            ->when($cursor > 0, fn ($query) => $query->where('id', '>', $cursor))
            ->when($retryIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $retryIds));
        $regularEquities = $selection->orderBy('id')->limit($remaining)->get($columns);
        if ($usesCursor && $retryEquities->isEmpty() && $regularEquities->isEmpty() && $remaining > 0 && $cursor > 0) {
            $cursor = 0;
            Cache::forget($cursorKey);
            $regularEquities = (clone $query)
                ->when($retryIds->isNotEmpty(), fn ($query) => $query->whereNotIn('id', $retryIds))
                ->orderBy('id')->limit($remaining)->get($columns);
        }
        $equities = $retryEquities->concat($regularEquities)->unique('id')->values();
        $instruments = $equities->concat($indexInstruments)->values();

        if ($instruments->isEmpty()) {
            $this->warn('No active equities or indices found with Upstox instrument keys.');
            return self::SUCCESS;
        }

        $symbolMap = $instruments->mapWithKeys(function ($instrument) {
            $key = $instrument->upstox_nse_instrument_key ?: $instrument->upstox_bse_instrument_key;

            return [$key => $instrument->nse_symbol ?: $instrument->bse_symbol];
        })->all();
        $coverage = $syncAll ? 'complete catalog pass' : "max {$maxBatches} batches";
        $this->info("Fetching news for {$instruments->count()} of {$totalEligible} eligible equity and index instruments in batches of {$batchSize} ({$coverage})...");

        $totalFetched = 0;
        $uniqueFetched = 0;
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $duplicates = 0;
        $failedBatches = 0;
        $successfulBatches = 0;
        $seenArticles = [];

        $batches = $instruments->chunk($batchSize)->values();
        foreach ($batches as $batchIndex => $batch) {
            $batchKeys = $batch->map(
                fn ($equity) => $equity->upstox_nse_instrument_key ?: $equity->upstox_bse_instrument_key
            )->filter()->unique()->values()->all();
            try {
                $articles = $upstox->news($batchKeys);
                $successfulBatches++;
                $totalFetched += count($articles);

                foreach ($articles as $article) {
                    $dedupeKey = ! empty($article['article_url'])
                        ? 'url:'.strtolower(rtrim(trim((string) $article['article_url']), '/'))
                        : 'content:'.hash('sha256', trim((string) $article['title'])."\n".trim((string) ($article['summary'] ?? '')));
                    if (isset($seenArticles[$dedupeKey])) {
                        $duplicates++;
                        continue;
                    }
                    $seenArticles[$dedupeKey] = true;
                    $uniqueFetched++;
                    $isin = $article['isin'] ?? null;
                    $symbol = $symbolMap[$article['instrument_key'] ?? ''] ?? null;

                    $originalTitle = trim((string) $article['title']);
                    $originalSummary = trim((string) ($article['summary'] ?? '')) ?: null;
                    $sourceHash = hash('sha256', $originalTitle."\n".($originalSummary ?? ''));
                    $newsQuery = MarketNews::query();
                    if (! empty($article['article_url'])) {
                        $newsQuery->where('article_url', $article['article_url']);
                    } else {
                        $newsQuery->where('original_title', $originalTitle)->where('isin', $isin);
                    }
                    $news = $newsQuery->first() ?: new MarketNews();
                    $isNew = ! $news->exists;
                    $sourceChanged = ! $news->exists || ! hash_equals((string) $news->source_hash, $sourceHash);

                    $news->fill([
                        'isin' => $isin,
                        'symbol' => $symbol,
                        'instrument_key' => $article['instrument_key'] ?? null,
                        'original_title' => $originalTitle,
                        'original_summary' => $originalSummary,
                        'source_hash' => $sourceHash,
                        'thumbnail' => $article['thumbnail'],
                        'article_url' => $article['article_url'],
                        'source' => $article['source'] ?? 'Upstox',
                        'published_at' => $article['published_at'] ?? now(),
                        'raw_data' => $article['raw_data'] ?? null,
                    ]);
                    if ($sourceChanged) {
                        $news->fill([
                            // Keep the source readable internally until NVIDIA creates a draft.
                            'title' => $originalTitle,
                            'summary' => $originalSummary,
                            'editorial_status' => MarketNews::STATUS_PENDING,
                            'is_published' => false,
                            'rewrite_model' => null,
                            'rewrite_version' => null,
                            'rewrite_error' => null,
                            'rewritten_at' => null,
                            'original_content' => null,
                            'source_fetched_at' => null,
                        ]);
                    }
                    $news->save();
                    if ($isNew) {
                        $created++;
                    } elseif ($sourceChanged) {
                        $updated++;
                    } else {
                        $unchanged++;
                    }
                }

                $batchEquityIds = $batch->pluck('id')->filter()->values();
                if ($usesCursor && $batchEquityIds->isNotEmpty()) {
                    $retryIds = $retryIds->diff($batchEquityIds)->values();
                    $retryIds->isEmpty()
                        ? Cache::forget($retryKey)
                        : Cache::forever($retryKey, $retryIds->all());
                    $cursor = max($cursor, (int) $batchEquityIds->max());
                    Cache::forever($cursorKey, $cursor);
                }
            } catch (Throwable $e) {
                $failedBatches++;
                $this->warn("News batch error: {$e->getMessage()} This batch will be retried on the next run.");
                report($e);
                if (str_contains($e->getMessage(), 'HTTP 401')
                    || str_contains($e->getMessage(), 'No active Upstox token')) {
                    $skipped = count($batches) - $batchIndex - 1;
                    if ($skipped > 0) {
                        $this->warn("Remaining {$skipped} news batches were skipped because Upstox authorization is unavailable.");
                    }
                    break;
                }

                if ($usesCursor) {
                    $batchEquityIds = $batch->pluck('id')->filter()->values();
                    if ($batchEquityIds->isNotEmpty()) {
                        $retryIds = $retryIds->merge($batchEquityIds)->unique()->values();
                        Cache::forever($retryKey, $retryIds->all());
                        $cursor = max($cursor, (int) $batchEquityIds->max());
                        Cache::forever($cursorKey, $cursor);
                    }
                }
            }
        }

        $this->info("News sync complete: {$totalFetched} fetched, {$uniqueFetched} unique, {$duplicates} duplicate; {$created} created, {$updated} updated, {$unchanged} unchanged; {$failedBatches} batches failed.");

        // Preserve successful work, but fail when the provider could not sync
        // any batch so monitoring can alert while the cursor retains the retry.
        return $failedBatches > 0 && $successfulBatches === 0
            ? self::FAILURE
            : self::SUCCESS;
    }
}
