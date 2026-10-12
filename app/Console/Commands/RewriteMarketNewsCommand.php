<?php

namespace App\Console\Commands;

use App\Exceptions\GroqRateLimitException;
use App\Models\MarketNews;
use App\Services\NvidiaNewsRewriter;
use Illuminate\Console\Command;
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

        $statuses = [MarketNews::STATUS_PENDING];
        if ($this->option('retry')) {
            $statuses[] = MarketNews::STATUS_FAILED;
        }

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
                if ($news->editorial_status === MarketNews::STATUS_FAILED) {
                    $news->forceFill([
                        'original_content' => null,
                        'source_fetched_at' => null,
                    ])->save();
                }
                $rewriter->rewrite($news);
                $completed++;
                $this->line("Generated news #{$news->id}; awaiting admin approval.");
            } catch (GroqRateLimitException $exception) {
                $deferred++;
                $this->warn("News #{$news->id} deferred until NVIDIA quota is available again.");
                break;
            } catch (Throwable $exception) {
                $failed++;
                $this->warn("News #{$news->id}: {$exception->getMessage()}");
            }
        }

        $this->info("News rewrite complete: {$completed} generated for review, {$deferred} deferred, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
