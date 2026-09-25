<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Services\UpstoxCorporateActionSyncService;
use Illuminate\Console\Command;

class SyncUpstoxEventsCommand extends Command
{
    protected $signature = 'market:sync-upstox-events
        {--limit=25 : Number of equities to check per run}
        {--isin=* : Specific ISINs to sync}';

    protected $description = 'Sync corporate events, meetings, and dividends separately from Upstox';

    public function handle(UpstoxCorporateActionSyncService $syncService): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $query = Equity::query()
            ->where('is_active', true)
            ->whereNotNull('upstox_nse_instrument_key')
            ->where('upstox_nse_instrument_key', '<>', '');

        $isinFilter = collect($this->option('isin'))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))
            ->filter()
            ->unique();

        if ($isinFilter->isNotEmpty()) {
            $query->whereIn('isin', $isinFilter->all());
        }

        // To allow continuous 1-minute execution covering various stocks, order by random or recent
        $equities = $query->inRandomOrder()->limit($limit)->get();

        if ($equities->isEmpty()) {
            $this->warn('No eligible equities found.');
            return self::SUCCESS;
        }

        $this->info("Syncing corporate events for {$equities->count()} equities...");
        $stats = $syncService->syncForEquities($equities, ['EVENT', 'DIVIDEND', 'RIGHTS']);

        $this->info("Events sync complete: {$stats['fetched']} total actions found, {$stats['saved']} event/dividend records saved.");
        return self::SUCCESS;
    }
}
