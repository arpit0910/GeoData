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

    protected $description = 'Rewrite, verify, and publish market news automatically';

    public function handle(NvidiaNewsRewriter $rewriter): int
    {
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
            ->orderBy('id')
            ->limit(max(1, min(100, (int) $this->option('limit'))))
            ->get();

        $completed = 0;
        $failed = 0;
        $deferred = 0;
        foreach ($items as $news) {
            try {
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
