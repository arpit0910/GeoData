<?php

namespace App\Console\Commands;

use App\Services\UpstoxInsightsSyncService;
use App\Services\MarketNudgeService;
use Illuminate\Console\Command;
use Throwable;

class SyncUpstoxInsightsCommand extends Command
{
    protected $signature = 'market:sync-upstox-insights
        {--only= : Sync only "ipos" or "activity"}
        {--delay=100 : Delay in milliseconds between IPO detail requests}';

    protected $description = 'Synchronize public IPO and FII/DII activity data from Upstox';

    public function handle(UpstoxInsightsSyncService $syncService, MarketNudgeService $nudgeService): int
    {
        $only = strtolower(trim((string) $this->option('only')));
        if (!in_array($only, ['', 'ipos', 'activity'], true)) {
            $this->error('The --only option must be either ipos or activity.');

            return self::INVALID;
        }

        try {
            if ($only !== 'activity') {
                $stats = $syncService->syncIpos(max(0, min((int) $this->option('delay'), 5000)));
                $this->info("IPOs synchronized. Received: {$stats['received']}; saved: {$stats['saved']}; detail errors: {$stats['detail_errors']}.");
            }
            if ($only !== 'ipos') {
                $stats = $syncService->syncInstitutionalActivity();
                $this->info("Institutional activity synchronized. Received: {$stats['received']}; saved: {$stats['saved']}.");
            }
            $created = match ($only) {
                'ipos' => $nudgeService->generateOpenIpoNudges(),
                'activity' => $nudgeService->generateInstitutionalNudges(),
                default => $nudgeService->generateAll(),
            };
            $this->info("Market nudges generated. Created: {$created}.");
        } catch (Throwable $exception) {
            $this->error('Upstox insights synchronization failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
