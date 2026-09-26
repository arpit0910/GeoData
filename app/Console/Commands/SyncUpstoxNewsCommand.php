<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Models\MarketNews;
use App\Services\UpstoxMarketDataService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Throwable;

class SyncUpstoxNewsCommand extends Command
{
    protected $signature = 'market:sync-upstox-news
        {--batch-size=30 : Instrument keys per Upstox news request (max 30)}
        {--limit= : Optional maximum instruments to query; all eligible instruments when omitted}
        {--isin=* : Specific ISINs to fetch news for}';

    protected $description = 'Sync latest market and stock news from Upstox API';

    public function handle(UpstoxMarketDataService $upstox): int
    {
        $batchSize = min(30, max(1, (int) $this->option('batch-size')));
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

        $query->orderBy('id');
        if ($limit !== null) {
            $query->limit($limit);
        }
        $equities = $query->get([
            'id', 'isin', 'nse_symbol', 'bse_symbol',
            'upstox_nse_instrument_key', 'upstox_bse_instrument_key',
        ]);

        if ($equities->isEmpty()) {
            $this->warn('No active equities found with Upstox instrument keys.');
            return self::SUCCESS;
        }

        $symbolMap = $equities->mapWithKeys(fn ($equity) => [
            $equity->isin => $equity->nse_symbol ?: $equity->bse_symbol,
        ])->all();
        $instrumentKeys = $equities->map(
            fn ($equity) => $equity->upstox_nse_instrument_key ?: $equity->upstox_bse_instrument_key
        )->filter()->unique()->values()->all();

        $this->info("Fetching news for up to {$equities->count()} instruments in batches of {$batchSize}...");

        $totalFetched = 0;
        $totalSaved = 0;

        foreach (array_chunk($instrumentKeys, $batchSize) as $batch) {
            try {
                $articles = $upstox->news($batch);
                $totalFetched += count($articles);

                foreach ($articles as $article) {
                    $isin = $article['isin'] ?? null;
                    $symbol = $isin && isset($symbolMap[$isin]) ? $symbolMap[$isin] : null;

                    // Match or create news item based on title or article_url
                    $attributes = [];
                    if (!empty($article['article_url'])) {
                        $attributes['article_url'] = $article['article_url'];
                    } else {
                        $attributes['title'] = $article['title'];
                        $attributes['isin'] = $isin;
                    }

                    MarketNews::updateOrCreate(
                        $attributes,
                        [
                            'isin' => $isin,
                            'symbol' => $symbol,
                            'instrument_key' => $article['instrument_key'] ?? null,
                            'title' => $article['title'],
                            'summary' => $article['summary'],
                            'thumbnail' => $article['thumbnail'],
                            'article_url' => $article['article_url'],
                            'source' => $article['source'] ?? 'Upstox',
                            'published_at' => $article['published_at'] ?? now(),
                            'raw_data' => $article['raw_data'] ?? null,
                        ]
                    );

                    $totalSaved++;
                }
            } catch (Throwable $e) {
                $this->warn("News batch error: {$e->getMessage()}");
            }
        }

        $this->info("News sync complete: {$totalFetched} articles fetched, {$totalSaved} saved/updated.");
        return self::SUCCESS;
    }
}
