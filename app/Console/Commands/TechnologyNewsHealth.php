<?php

namespace App\Console\Commands;

use App\Models\{NewsArticle, NewsSource, NewsSyncLog};
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TechnologyNewsHealth extends Command
{
    protected $signature = 'news:health';
    protected $description = 'Exit nonzero for stale, failing, or interrupted technology news ingestion';
    public function handle(): int
    {
        $unhealthy = false;
        $sources = NewsSource::where('is_active', true)->get();
        foreach ($sources as $source) {
            $stale = !$source->displayPermitted() || !$source->last_success_at
                || $source->last_success_at->lt(now('UTC')->subMinutes(max(180, $source->sync_interval_minutes * 2)));
            $bad = $stale || $source->consecutive_failures > 0;
            $unhealthy = $unhealthy || $bad;
            $this->line($source->name.': '.($bad ? 'UNHEALTHY' : 'healthy').' | last success UTC: '.($source->last_success_at ?: 'never'));
        }
        $interrupted = NewsSyncLog::where('status', 'running')->where('started_at', '<', now('UTC')->subHour())->count();
        $queued = DB::table('news_jobs')->where('created_at', '<', now('UTC')->subHour()->timestamp)->count();
        if ($interrupted || $queued) { $this->error($interrupted.' interrupted runs; '.$queued.' queue jobs older than one hour.'); $unhealthy = true; }
        $rewriteBacklog = NewsArticle::whereIn('editorial_status', ['pending', 'processing', 'failed'])
            ->whereHas('source', fn ($q) => $q->where('is_active', true)->where('allow_ai_rewrite', true))
            ->where('updated_at', '<', now('UTC')->subHour())->count();
        if ($rewriteBacklog) { $this->error($rewriteBacklog.' AI drafts have not progressed for over an hour.'); $unhealthy = true; }
        return $unhealthy || $sources->isEmpty() ? 1 : 0;
    }
}
