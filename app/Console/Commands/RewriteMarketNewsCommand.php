<?php

namespace App\Console\Commands;

use App\Exceptions\GroqRateLimitException;
use App\Models\MarketNews;
use App\Services\NvidiaNewsRewriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RewriteMarketNewsCommand extends Command
{
    protected $signature = 'market:rewrite-news
        {--limit=20 : Maximum news records to rewrite and verify}
        {--id=* : Rewrite specific news record IDs}
        {--retry : Include previously failed rewrites}';

    protected $description = 'Rewrite and verify market news for publication review';

    public function handle(NvidiaNewsRewriter $rewriter): int
    {
        $staleAfterMinutes = max(5, (int) config('services.nvidia.processing_timeout_minutes', 30));
        $recovered = MarketNews::query()
            ->where('editorial_status', MarketNews::STATUS_PROCESSING)
            ->where('updated_at', '<=', now()->subMinutes($staleAfterMinutes))
            ->update([
                'editorial_status' => MarketNews::STATUS_PENDING,
                'rewrite_error' => 'The previous generation worker stopped before completion. The story was queued for automatic retry.',
                'rewrite_retry_at' => null,
                'updated_at' => now(),
            ]);
        if ($recovered > 0) {
            $this->warn("Recovered {$recovered} stale processing ".($recovered === 1 ? 'story' : 'stories').' for retry.');
        }

        $statuses = [
            MarketNews::STATUS_PENDING,
            MarketNews::STATUS_READY,
            MarketNews::STATUS_FAILED,
        ];

        $ids = collect($this->option('id'))->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $items = MarketNews::query()
            ->when(
                $ids->isNotEmpty(),
                fn ($query) => $query->whereIn('id', $ids->all()),
                fn ($query) => $query->whereIn('editorial_status', $statuses)
            )
            ->where(function ($query) {
                $query->whereNull('rewrite_retry_at')
                    ->orWhere('rewrite_retry_at', '<=', now());
            })
            ->orderByRaw("CASE WHEN editorial_status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->limit(max(1, min(100, (int) $this->option('limit'))))
            ->get();

        $completed = 0;
        $failed = 0;
        $deferred = 0;
        foreach ($items as $news) {
            try {
                if ($news->editorial_status === MarketNews::STATUS_READY) {
                    $this->publish($news);
                    $completed++;
                    $this->line("Published verified news #{$news->id}.");
                    continue;
                }
                if ($news->editorial_status === MarketNews::STATUS_FAILED) {
                    $news->forceFill([
                        'original_content' => null,
                        'source_fetched_at' => null,
                    ])->save();
                }
                $rewriter->rewrite($news);
                $this->publish($news->refresh());
                $completed++;
                $this->line("Generated and published news #{$news->id}.");
            } catch (GroqRateLimitException $exception) {
                $deferred++;
                $this->warn("News #{$news->id} deferred until NVIDIA quota is available again.");
                break;
            } catch (Throwable $exception) {
                $failed++;
                $this->warn("News #{$news->id}: {$exception->getMessage()}");
            }
        }

        $this->info("News rewrite complete: {$completed} published, {$deferred} deferred, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function publish(MarketNews $news): void
    {
        if ($news->editorial_status !== MarketNews::STATUS_READY
            || ! $news->rewritten_at
            || trim((string) $news->title) === ''
            || trim((string) $news->summary) === '') {
            throw new \RuntimeException('The generated story did not produce a verified publishable draft.');
        }

        $news->forceFill([
            'editorial_status' => MarketNews::STATUS_PUBLISHED,
            'is_published' => true,
            'reviewed_at' => now(),
            'reviewed_by' => null,
        ])->save();
        Cache::forget('website.market.overview');
    }
}
