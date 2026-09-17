<?php

namespace App\Console\Commands;

use App\Services\ExchangeCalendarSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncExchangeCalendarCommand extends Command
{
    protected $signature = 'exchange-calendar:sync
        {--exchange=* : Exchange(s) to sync: NSE or BSE}
        {--year=* : Four-digit calendar year(s); defaults to current and next year}';

    protected $description = 'Sync the combined NSE/BSE equity holiday, weekend, and Muhurat calendar';

    public function handle(ExchangeCalendarSyncService $service): int
    {
        $exchanges = array_values(array_unique(array_map('strtoupper', $this->option('exchange') ?: ['NSE', 'BSE'])));
        $years = $this->option('year') ?: [now('Asia/Kolkata')->year, now('Asia/Kolkata')->addYear()->year];
        $years = array_values(array_unique(array_map('intval', $years)));

        foreach ($exchanges as $exchange) {
            if (! in_array($exchange, ['NSE', 'BSE'], true)) {
                $this->error("Invalid exchange '{$exchange}'. Valid values: NSE, BSE.");
                return self::INVALID;
            }
        }
        foreach ($years as $year) {
            if ($year < 2000 || $year > 2100) {
                $this->error("Invalid year '{$year}'. Expected a year from 2000 through 2100.");
                return self::INVALID;
            }
        }

        $failed = false;
        foreach ($years as $year) {
            if ($exchanges === ['NSE', 'BSE'] || $exchanges === ['BSE', 'NSE']) {
                try {
                    $result = $service->syncCombined($year);
                    $this->info("NSE & BSE {$year}: synced {$result['synced']} combined calendar dates.");
                } catch (Throwable $exception) {
                    $failed = true;
                    report($exception);
                    $this->error("NSE & BSE {$year}: {$exception->getMessage()}");
                }
                continue;
            }
            foreach ($exchanges as $exchange) {
                try {
                    $result = $service->sync($exchange, $year);
                    if ($result['skipped']) {
                        $this->warn("{$exchange} {$year}: the exchange has not published calendar events for this year.");
                    } else {
                        $this->info("{$exchange} {$year}: synced {$result['synced']} calendar events.");
                    }
                } catch (Throwable $exception) {
                    $failed = true;
                    report($exception);
                    $this->error("{$exchange} {$year}: {$exception->getMessage()}");
                }
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
