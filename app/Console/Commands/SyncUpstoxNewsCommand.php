<?php

namespace App\Console\Commands;

use App\Models\Equity;
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
        {--limit= : Optional maximum instruments to query; all eligible instruments when omitted}
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
            ->whereIn('series', ['EQ', 'BE', 'SM', 'BZ'])
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

        $totalEligible = (clone $query)->count();
        $cursorKey = 'market_news_sync:last_equity_id';
        $usesCursor = $isinFilter->isEmpty() && $limit === null;
        if ($this->option('reset-cursor')) {
            Cache::forget($cursorKey);
        }
        $cursor = $usesCursor ? max(0, (int) Cache::get($cursorKey, 0)) : 0;
        $selectionLimit = $limit ?? ($batchSize * $maxBatches);
        $columns = [
            'id', 'isin', 'nse_symbol', 'bse_symbol',
            'upstox_nse_instrument_key', 'upstox_bse_instrument_key',
        ];
        $selection = (clone $query)->when($cursor > 0, fn ($query) => $query->where('id', '>', $cursor));
        $equities = $selection->orderBy('id')->limit($selectionLimit)->get($columns);
        if ($usesCursor && $equities->isEmpty() && $cursor > 0) {
            $cursor = 0;
            Cache::forget($cursorKey);
            $equities = (clone $query)->orderBy('id')->limit($selectionLimit)->get($columns);
        }

        if ($equities->isEmpty()) {
            $this->warn('No active equities found with Upstox instrument keys.');
            return self::SUCCESS;
        }

        $symbolMap = $equities->mapWithKeys(fn ($equity) => [
            $equity->isin => $equity->nse_symbol ?: $equity->bse_symbol,
        ])->all();
        $this->info("Fetching news for {$equities->count()} of {$totalEligible} eligible stocks in batches of {$batchSize} (max {$maxBatches} batches)...");

        $totalFetched = 0;
        $uniqueFetched = 0;
        $created = 0;
        $updated = 0;
        $unchanged = 0;
        $duplicates = 0;
        $failedBatches = 0;
        $seenArticles = [];

        $batches = $equities->chunk($batchSize)->values();
        foreach ($batches as $batchIndex => $batch) {
            $batchKeys = $batch->map(
                fn ($equity) => $equity->upstox_nse_instrument_key ?: $equity->upstox_bse_instrument_key
            )->filter()->unique()->values()->all();
            try {
                $articles = $upstox->news($batchKeys);
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
                    $symbol = $isin && isset($symbolMap[$isin]) ? $symbolMap[$isin] : null;

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
                            // Keep the source readable internally until Gemini creates a draft.
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

                if ($usesCursor && ($lastEquity = $batch->last())) {
                    Cache::forever($cursorKey, $lastEquity->id);
                }
            } catch (Throwable $e) {
                $failedBatches++;
                $this->warn("News batch error: {$e->getMessage()}");
                report($e);
                if (str_contains($e->getMessage(), 'HTTP 401')
                    || str_contains($e->getMessage(), 'No active Upstox token')) {
                    $skipped = count($batches) - $batchIndex - 1;
                    if ($skipped > 0) {
                        $this->warn("Remaining {$skipped} news batches were skipped because Upstox authorization is unavailable.");
                    }
                    break;
                }
                if ($usesCursor && ($lastEquity = $batch->last())) {
                    Cache::forever($cursorKey, $lastEquity->id);
                }
            }
        }

        $this->info("News sync complete: {$totalFetched} fetched, {$uniqueFetched} unique, {$duplicates} duplicate; {$created} created, {$updated} updated, {$unchanged} unchanged; {$failedBatches} batches failed.");

        return $failedBatches > 0 ? self::FAILURE : self::SUCCESS;
    }
}
