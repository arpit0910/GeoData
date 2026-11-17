<?php

namespace App\Console\Commands;

use App\Exceptions\GroqRateLimitException;
use App\Models\NewsArticle;
use App\Services\TechnologyNews\{ArticleRewriter, NewsCache};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RewriteTechnologyNews extends Command
{
    protected $signature = 'news:rewrite {--limit=20} {--source=} {--id=*}';
    protected $description = 'Rewrite, independently verify and automatically publish technology news';

    public function handle(ArticleRewriter $rewriter): int
    {
        if (!ctype_digit((string) $this->option('limit')) || ($this->option('source') && !ctype_digit((string) $this->option('source')))
            || collect($this->option('id'))->contains(fn ($id) => !ctype_digit((string) $id))) {
            $this->error('Limit, source and article IDs must be positive integers.'); return 1;
        }
        NewsArticle::where('editorial_status', NewsArticle::STATUS_PROCESSING)
            ->where('updated_at', '<', now('UTC')->subMinutes(30))
            ->update(['editorial_status' => NewsArticle::STATUS_PENDING, 'rewrite_error' => 'Previous worker interrupted; queued again.']);
        $items = NewsArticle::with('source')->whereIn('editorial_status', [NewsArticle::STATUS_PENDING, NewsArticle::STATUS_FAILED])
            ->whereHas('source', fn ($q) => $q->where('is_active', true)->where('allow_ai_rewrite', true)->whereNotNull('permissions_verified_at')->whereIn('display_mode', ['headline', 'excerpt']))
            ->where(fn ($q) => $q->whereNull('rewrite_retry_at')->orWhere('rewrite_retry_at', '<=', now('UTC')))
            ->when($this->option('source'), fn ($q) => $q->where('source_id', $this->option('source')))
            ->when($this->option('id'), fn ($q) => $q->whereIn('id', $this->option('id')))
            ->orderBy('updated_at')->orderBy('id')->limit(max(1, min(100, (int) $this->option('limit'))))->get();
        $published = 0; $failed = 0;
        foreach ($items as $article) {
            $hash = $article->content_hash;
            $claimed = NewsArticle::whereKey($article->id)->where('content_hash', $hash)
                ->whereIn('editorial_status', [NewsArticle::STATUS_PENDING, NewsArticle::STATUS_FAILED])
                ->update(['editorial_status' => NewsArticle::STATUS_PROCESSING, 'rewrite_error' => null, 'updated_at' => now('UTC')]);
            if (!$claimed) { continue; }
            try {
                $draft = $rewriter->generateDraft($article);
                if (trim((string) ($draft['ai_title'] ?? '')) === '' || trim((string) ($draft['ai_summary'] ?? '')) === ''
                    || trim((string) ($draft['ai_content'] ?? '')) === '' || (int) ($draft['rewrite_version'] ?? 0) < 2) {
                    throw new \RuntimeException('No verified draft was returned.');
                }
                DB::transaction(function () use ($article, $hash, $draft, &$published) {
                    $current = NewsArticle::whereKey($article->id)->lockForUpdate()->firstOrFail();
                    if ($current->content_hash !== $hash || $current->editorial_status !== NewsArticle::STATUS_PROCESSING || !$current->source->rewritePermitted()) { return; }
                    // status=hidden is an editorial override and must survive automatic approval.
                    $current->fill(array_merge($draft, ['editorial_status' => NewsArticle::STATUS_PUBLISHED,
                        'rewrite_source_hash' => $hash, 'rewritten_at' => now('UTC'), 'rewrite_retry_at' => null, 'rewrite_error' => null]))->save();
                    $published++;
                });
                $this->line('Verified and auto-approved technology story #'.$article->id.'.');
            } catch (\Throwable $e) {
                $failed++;
                $retryAt = now('UTC')->addSeconds($e instanceof GroqRateLimitException ? $e->retryAfterSeconds : 1800);
                $safeError = $e instanceof GroqRateLimitException ? 'AI quota cooldown; automatic retry scheduled.'
                    : (preg_match('/^(Technology news draft|The model returned|The draft|Full licensed|NVIDIA_API_KEY|This source|No verified draft)/', $e->getMessage())
                        ? mb_substr($e->getMessage(), 0, 700)
                        : 'AI rewrite or factual verification failed ('.class_basename($e).').');
                NewsArticle::whereKey($article->id)->where('content_hash', $hash)->where('editorial_status', NewsArticle::STATUS_PROCESSING)
                    ->update(['editorial_status' => NewsArticle::STATUS_FAILED, 'rewrite_error' => $safeError,
                        'rewrite_retry_at' => $retryAt, 'updated_at' => now('UTC')]);
                $this->warn('Technology story #'.$article->id.' failed verification or generation; retained for retry.');
                if ($e instanceof GroqRateLimitException) { break; }
            }
        }
        NewsCache::invalidate();
        $this->info('Technology rewrite complete: '.$published.' auto-approved, '.$failed.' failed/deferred.');
        return $failed ? 1 : 0;
    }
}
