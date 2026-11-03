<?php

namespace App\Console\Commands;

use App\Services\AmcSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncAmcIconsCommand extends Command
{
    protected $signature = 'amcs:sync-icons {--force : Download existing AMC icons again}';
    protected $description = 'Sync all AMC details and save small favicons locally from AMFI or official AMC websites';

    public function handle(AmcSyncService $service): int
    {
        set_time_limit(0);
        try {
            $stats = $service->sync((bool) $this->option('force'));
            foreach ($stats['errors'] as $error) {
                $this->warn($error);
            }
            $this->info("AMC sync: {$stats['saved']} saved, {$stats['downloaded']} favicons downloaded, {$stats['existing']} already present, ".count($stats['errors']).' failed.');
            return $stats['errors'] === [] ? self::SUCCESS : self::FAILURE;
        } catch (Throwable $exception) {
            $this->error('AMC sync failed: '.$exception->getMessage());
            return self::FAILURE;
        }
    }
}
