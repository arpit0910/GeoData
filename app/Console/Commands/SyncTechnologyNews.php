<?php

namespace App\Console\Commands;

use App\Services\TechnologyNews\Synchronizer;
use Illuminate\Console\Command;

class SyncTechnologyNews extends Command
{
    protected $signature = 'news:sync {--source= : One source ID} {--force : Ignore interval, but respect Retry-After}';
    protected $description = 'Synchronize permitted technology news sources independently';
    public function handle(Synchronizer $sync): int
    {
        if ($this->option('source') && !ctype_digit((string) $this->option('source'))) { $this->error('Invalid source ID.'); return 1; }
        $results = $sync->run($this->option('source') ? (int) $this->option('source') : null, (bool) $this->option('force'));
        foreach ($results as $id => $status) { $this->line('Source '.$id.': '.$status); }
        if (!$results) { $this->warn('No active sources. Run news:install and review source permissions.'); }
        // Run after fetching, even when one provider failed, so healthy sources progress.
        $rewrite = $this->call('news:rewrite', array_filter(['--source' => $this->option('source')]));
        return count(array_intersect($results, ['failed', 'partial'])) || $rewrite !== 0 ? 1 : 0;
    }
}
