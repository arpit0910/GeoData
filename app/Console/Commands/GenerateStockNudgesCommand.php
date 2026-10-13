<?php

namespace App\Console\Commands;

use App\Services\StockNudgeService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

class GenerateStockNudgesCommand extends Command
{
    protected $signature = 'stock-nudges:generate
        {type : market or eod}
        {--date= : Trading date for EOD generation (YYYY-MM-DD)}
        {--limit= : Maximum stocks and indices to inspect per asset type}';

    protected $description = 'Generate deduplicated market or end-of-day stock and index nudges';

    public function handle(StockNudgeService $nudges): int
    {
        $type = strtolower(trim((string) $this->argument('type')));
        if (!in_array($type, ['market', 'eod'], true)) {
            $this->error('Type must be market or eod.');
            return self::INVALID;
        }

        $limit = $this->option('limit');
        if ($limit !== null && (!ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error('--limit must be a positive integer.');
            return self::INVALID;
        }

        try {
            $date = $this->option('date')
                ? Carbon::createFromFormat('Y-m-d', (string) $this->option('date'), 'Asia/Kolkata')->startOfDay()
                : now('Asia/Kolkata')->startOfDay();
        } catch (InvalidArgumentException) {
            $this->error('--date must use YYYY-MM-DD.');
            return self::INVALID;
        }

        $limit = $limit === null ? null : (int) $limit;
        $stockCreated = $type === 'market'
            ? $nudges->generateMarket($limit)
            : $nudges->generateEod($date, $limit);
        $indexCreated = $type === 'market'
            ? $nudges->generateIndexMarket($limit)
            : $nudges->generateIndexEod($date, $limit);

        $this->info("Created {$stockCreated} stock and {$indexCreated} index {$type} nudge(s).");
        return self::SUCCESS;
    }
}
