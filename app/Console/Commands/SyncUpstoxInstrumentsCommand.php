<?php

namespace App\Console\Commands;

use App\Services\UpstoxInstrumentSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncUpstoxInstrumentsCommand extends Command
{
    protected $signature = 'equities:sync-upstox-instruments
        {file : Absolute or project-relative path to the Upstox complete.json file}
        {--dry-run : Analyse the file without changing the database}';

    protected $description = 'Add and update equities from the Upstox instrument master';

    public function handle(UpstoxInstrumentSyncService $syncService): int
    {
        $path = (string) $this->argument('file');
        if (!$this->isAbsolutePath($path)) {
            $path = base_path($path);
        }

        try {
            $stats = $syncService->sync($path, (bool) $this->option('dry-run'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Scanned', 'Eligible', 'Invalid', 'Unique ISINs', 'Matched', 'Unmatched DB', 'Add', 'Update'],
            [[
                $stats['scanned'],
                $stats['eligible_rows'],
                $stats['invalid_rows'],
                $stats['unique_isins'],
                $stats['existing'],
                $stats['unmatched_existing'],
                $stats['added'],
                $stats['updated'],
            ]]
        );
        $this->info($this->option('dry-run') ? 'Dry run complete; no records changed.' : 'Upstox instruments synced.');

        return self::SUCCESS;
    }

    private function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }
}
