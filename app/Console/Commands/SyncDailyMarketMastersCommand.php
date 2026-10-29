<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Throwable;

class SyncDailyMarketMastersCommand extends Command
{
    protected $signature = 'market:sync-daily-masters';
    protected $description = 'Update stock, mutual-fund and instrument masters in sequence';

    public function handle(): int
    {
        set_time_limit(0);
        $failed = [];

        // AMFI creates new fund master rows before the provider metadata is matched.
        // These master-file downloads do not require an Upstox access token.
        foreach ([
            'equities:sync-upstox-instruments',
            'equities:sync-metadata',
            'mf:sync-and-calculate',
            'mf:sync-upstox-daily',
            'market:sync-global-instruments',
        ] as $command) {
            $this->info("Starting {$command}...");
            try {
                if ($this->call($command) !== self::SUCCESS) {
                    $failed[] = $command;
                }
            } catch (Throwable $exception) {
                $failed[] = $command;
                $this->error("{$command}: {$exception->getMessage()}");
            }
        }

        if ($failed !== []) {
            $this->error('Daily master sync failed for: '.implode(', ', $failed));
            return self::FAILURE;
        }

        $this->info('Daily stock, mutual-fund and instrument master sync completed.');
        return self::SUCCESS;
    }
}
