<?php

namespace App\Console\Commands;

use App\Services\MfService;
use App\Services\UpstoxMutualFundNavSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncUpstoxMutualFundsCommand extends Command
{
    protected $signature = 'mf:sync-upstox-daily
        {--dry-run : Download and validate without writing to the database}
        {--skip-returns : Do not calculate returns for the imported NAV date}';

    protected $description = 'Sync latest mutual-fund NAVs from the daily Upstox instrument rates';

    public function handle(
        UpstoxMutualFundNavSyncService $syncService,
        MfService $mfService
    ): int {
        try {
            $stats = $syncService->sync((bool) $this->option('dry-run'));
        } catch (Throwable $exception) {
            $this->error('Upstox mutual-fund NAV sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Upstox MF NAVs: received=%s, matched=%s, saved=%s, unmatched=%s, latest=%s.',
            number_format($stats['received']),
            number_format($stats['matched']),
            number_format($stats['saved']),
            number_format($stats['unmatched']),
            $stats['latest_date'] ?? 'none'
        ));

        if (! $this->option('dry-run') && ! $this->option('skip-returns') && $stats['latest_date']) {
            $result = $mfService->computeReturnsForDate($stats['latest_date']);
            $this->info("Returns updated for {$result['rows_written']} funds.");
        }

        return self::SUCCESS;
    }
}
