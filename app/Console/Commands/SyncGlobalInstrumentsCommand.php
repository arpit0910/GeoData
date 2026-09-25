<?php

namespace App\Console\Commands;

use App\Services\GlobalInstrumentSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncGlobalInstrumentsCommand extends Command
{
    protected $signature = 'market:sync-global-instruments';

    protected $description = 'Download and synchronize the global indices and indicators instrument master';

    public function handle(GlobalInstrumentSyncService $syncService): int
    {
        try {
            $stats = $syncService->sync();
        } catch (Throwable $exception) {
            $this->error('Global instrument synchronization failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            "Global instruments synchronized. Received: {$stats['received']}; "
            ."saved: {$stats['saved']}; deactivated: {$stats['deactivated']}."
        );

        return self::SUCCESS;
    }
}
