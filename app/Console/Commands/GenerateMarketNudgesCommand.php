<?php

namespace App\Console\Commands;

use App\Services\MarketNudgeService;
use Illuminate\Console\Command;

class GenerateMarketNudgesCommand extends Command
{
    protected $signature = 'market:nudges:generate {--type=all : all, ipo, or activity}';

    protected $description = 'Generate deduplicated open IPO and FII/DII activity nudges';

    public function handle(MarketNudgeService $nudges): int
    {
        $type = strtolower(trim((string) $this->option('type')));
        if (!in_array($type, ['all', 'ipo', 'activity'], true)) {
            $this->error('--type must be all, ipo, or activity.');
            return self::INVALID;
        }

        $created = match ($type) {
            'ipo' => $nudges->generateOpenIpoNudges(),
            'activity' => $nudges->generateInstitutionalNudges(),
            default => $nudges->generateAll(),
        };

        $this->info("Created {$created} market nudge(s).");
        return self::SUCCESS;
    }
}
