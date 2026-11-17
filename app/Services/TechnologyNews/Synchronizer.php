<?php

namespace App\Services\TechnologyNews;

use App\Models\{NewsArticle, NewsCategory, NewsSource, NewsSyncLog};
use Illuminate\Support\Facades\{DB, Log};
use Illuminate\Support\Str;

class Synchronizer
{
    private $providers;
    private $normalizer;
    public function __construct(Providers $providers, Normalizer $normalizer)
    {
        $this->providers = $providers; $this->normalizer = $normalizer;
    }

    public function run(?int $sourceId = null, bool $force = false): array
    {
        $results = [];
        foreach (NewsSource::where('is_active', true)->when($sourceId, fn ($q) => $q->whereKey($sourceId))->orderBy('id')->cursor() as $source) {
            try { $results[$source->id] = $this->sync($source, $force); }
            catch (\Throwable $e) {
                $results[$source->id] = 'failed';
                Log::error('technology_news.source_failed', ['source_id' => $source->id, 'exception' => get_class($e)]);
            }
        }
        return $results;
    }

    public function sync(NewsSource $source, bool $force = false): string
    {
        if (!$source->displayPermitted()) { return 'disabled'; }
        $now = now('UTC');
        if ($source->retry_after_at && $source->retry_after_at->gt($now)) { return 'deferred'; }
        if (!$force && $source->last_sync_at && $source->last_sync_at->copy()->startOfHour()->addMinutes($source->sync_interval_minutes)->gt($now)) { return 'not_due'; }
        $owner = (string) Str::uuid(); $name = 'source-'.$source->id;
        // Atomic insert/update works across hosts and does not depend on the cache driver.
        DB::table('news_sync_locks')->insertOrIgnore(['name' => $name, 'owner' => '', 'expires_at' => $now->copy()->subSecond()]);
        $acquired = DB::table('news_sync_locks')->where('name', $name)->where('expires_at', '<', $now)
            ->update(['owner' => $owner, 'expires_at' => $now->copy()->addHour()]);
        if (!$acquired) { return 'locked'; }
        $log = null; $start = microtime(true);
        try {
            $source->refresh();
            if (!$source->displayPermitted()) { return 'disabled'; }
            if (!$force && $source->last_sync_at && $source->last_sync_at->copy()->startOfHour()->addMinutes($source->sync_interval_minutes)->gt($now)) { return 'not_due'; }
            $log = NewsSyncLog::create(['source_id' => $source->id, 'started_at' => $now, 'created_at' => $now, 'status' => 'running']);
            $source->update(['last_sync_at' => $now]);
            $payload = $this->providers->fetch($source);
            foreach ($payload['items'] as $row) {
                $log->fetched_count++;
                try {
                    $article = $this->normalizer->article($row);
                    if ($source->display_mode !== 'excerpt' && !$source->rewritePermitted()) { $article['description'] = null; }
                    if (!$source->rewritePermitted() || !$source->allow_feed_content) { $article['content'] = null; }
                    // Track the permitted rewrite inputs, including excerpt/tag changes.
                    $article['content_hash'] = hash('sha256', json_encode([
                        $article['title'], $article['description'], $article['content'], $article['tags'],
                        $article['author_name'], $article['canonical_url'],
                        $article['published_at']->toIso8601String(),
                    ], JSON_THROW_ON_ERROR));
                } catch (\Throwable $e) { $log->skipped_count++; continue; }
                try {
                    $outcome = $this->store($source, $article);
                    $log->{$outcome.'_count'}++;
                    if ($outcome !== 'inserted') { $log->duplicate_count++; }
                } catch (\Throwable $e) {
                    $log->failed_count++;
                    $log->error_message = 'One or more article transactions failed; inspect application logs.';
                    Log::error('technology_news.article_failed', ['source_id' => $source->id, 'exception' => get_class($e)]);
                }
            }
            $status = $log->failed_count ? 'partial' : 'success';
            $source->update(array_merge([
                'last_success_at' => $status === 'success' ? now('UTC') : $source->last_success_at,
                'last_error' => $log->error_message, 'consecutive_failures' => $status === 'success' ? 0 : $source->consecutive_failures + 1,
                'retry_after_at' => null,
            ], array_intersect_key($payload, array_flip(['etag', 'last_modified']))));
            $log->status = $status;
        } catch (\Throwable $e) {
            $message = $e instanceof RateLimited || get_class($e) === \RuntimeException::class
                ? $e->getMessage()
                : ($e instanceof \Illuminate\Http\Client\ConnectionException
                    ? 'HTTPS connection failed. Check DNS, outbound access, and the trusted CA bundle.'
                    : 'Synchronization failed ('.class_basename($e).'). See application logs.');
            $source->update(['last_error' => $message, 'consecutive_failures' => $source->consecutive_failures + 1,
                'retry_after_at' => now('UTC')->addSeconds($e instanceof RateLimited ? $e->seconds : min(21600, 60 * (2 ** min(8, $source->consecutive_failures))))]);
            if ($log) { $log->status = 'failed'; $log->failed_count++; $log->error_message = $message; }
            Log::error('technology_news.sync_failed', ['source_id' => $source->id, 'exception' => get_class($e)]);
        } finally {
            try {
                if ($log) {
                    $log->completed_at = now('UTC'); $log->duration_ms = (int) ((microtime(true) - $start) * 1000); $log->save();
                    NewsCache::invalidate();
                    Log::info('technology_news.sync_completed', ['source_id' => $source->id, 'status' => $log->status, 'duration_ms' => $log->duration_ms]);
                }
            } finally {
                DB::table('news_sync_locks')->where('name', $name)->where('owner', $owner)->delete();
            }
        }
        return $log ? $log->status : 'failed';
    }

    private function store(NewsSource $source, array $data): string
    {
        try {
            return DB::transaction(function () use ($source, $data) {
                $reference = DB::table('news_article_references')->where('source_id', $source->id)->where('source_article_id', $data['source_article_id'])->first();
                $existing = $reference ? NewsArticle::lockForUpdate()->find($reference->article_id)
                    : NewsArticle::where('url_hash', $data['url_hash'])->lockForUpdate()->first();
                if ($existing) {
                    $existing->last_seen_at = now('UTC');
                    // Cross-source observations never overwrite the original publisher's metadata.
                    if ($existing->source_id === $source->id && $existing->source_article_id === $data['source_article_id']) {
                        $updates = $data;
                        // Keep the original URL identity stable if providers later change their canonical URL.
                        unset($updates['url_hash'], $updates['canonical_url'], $updates['original_url'], $updates['source_article_id']);
                        if ($existing->content_hash !== $data['content_hash']) {
                            $existing->editorial_status = NewsArticle::STATUS_PENDING;
                            $existing->rewrite_retry_at = null;
                            $existing->rewrite_error = null;
                        }
                        $existing->fill($updates);
                        if (!$existing->editorial_category) { $existing->category_id = $this->categoryId($data); }
                    }
                    $existing->save();
                    DB::table('news_article_references')->updateOrInsert(['source_id' => $source->id, 'source_article_id' => $data['source_article_id']], ['article_id' => $existing->id, 'last_seen_at' => now('UTC')]);
                    return 'updated';
                }
                $article = NewsArticle::create(array_merge($data, ['source_id' => $source->id,
                    'publisher_name' => $source->source_type === 'hackernews' ? parse_url($data['original_url'], PHP_URL_HOST) : $source->name,
                    'category_id' => $this->categoryId($data), 'slug' => Str::limit(Str::slug($data['title']), 120, '').'-'.Str::lower(Str::random(12)),
                    'fetched_at' => now('UTC'), 'last_seen_at' => now('UTC'), 'status' => 'published']));
                DB::table('news_article_references')->insert(['source_id' => $source->id, 'source_article_id' => $data['source_article_id'], 'article_id' => $article->id, 'last_seen_at' => now('UTC')]);
                // Similar headlines are review candidates, never automatic merges.
                $nearby = NewsArticle::where('id', '<>', $article->id)->whereBetween('published_at', [$article->published_at->copy()->subDay(), $article->published_at->copy()->addDay()])->orderByDesc('published_at')->limit(100)->get(['id', 'title', 'title_hash', 'description']);
                foreach ($nearby as $candidate) {
                    similar_text(mb_strtolower($article->title), mb_strtolower($candidate->title), $similarity);
                    $descriptionSimilar = false;
                    if (mb_strlen($article->description ?? '') >= 120 && mb_strlen($candidate->description ?? '') >= 120) {
                        $tokens = fn ($s) => array_unique(preg_split('/[^\pL\pN]+/u', mb_strtolower($s), -1, PREG_SPLIT_NO_EMPTY));
                        $a = $tokens($article->description); $b = $tokens($candidate->description);
                        $descriptionSimilar = count($a) >= 20 && count(array_intersect($a, $b)) / max(1, count(array_unique(array_merge($a, $b)))) >= 0.9;
                    }
                    if ($candidate->title_hash === $article->title_hash || ($similarity >= 90 && mb_strlen($article->title) > 30) || $descriptionSimilar) {
                        DB::table('news_duplicate_candidates')->insertOrIgnore(['article_id' => $article->id, 'candidate_id' => $candidate->id, 'reason' => ($descriptionSimilar ? 'Similar permitted excerpt' : 'Similar headline').' within 24 hours; different canonical URL', 'status' => 'pending', 'created_at' => now('UTC'), 'updated_at' => now('UTC')]);
                    }
                }
                return 'inserted';
            }, 3);
        } catch (\Illuminate\Database\QueryException $e) {
            if (!in_array((string) $e->getCode(), ['23000', '23505'], true)) { throw $e; }
            // A different source may win a concurrent canonical-URL insertion.
            $existing = NewsArticle::where('url_hash', $data['url_hash'])->first();
            if (!$existing) { throw $e; }
            DB::table('news_article_references')->updateOrInsert(['source_id' => $source->id, 'source_article_id' => $data['source_article_id']], ['article_id' => $existing->id, 'last_seen_at' => now('UTC')]);
            $existing->update(['last_seen_at' => now('UTC')]);
            return 'updated';
        }
    }

    private function categoryId(array $data): int
    {
        return (int) NewsCategory::where('slug', $this->normalizer->category($data))->value('id');
    }
}
