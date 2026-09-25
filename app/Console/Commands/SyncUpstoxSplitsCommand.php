<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Services\UpstoxCorporateActionSyncService;
use Illuminate\Console\Command;

class SyncUpstoxSplitsCommand extends Command
{
    protected $signature = 'market:sync-upstox-splits
        {--limit=25 : Number of equities to check per run}
        {--isin=* : Specific ISINs to sync}';

    protected $description = 'Sync stock splits separately from Upstox';

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

        $equities = $query->inRandomOrder()->limit($limit)->get();

        if ($equities->isEmpty()) {
            $this->warn('No eligible equities found.');
            return self::SUCCESS;
        }

        $this->info("Checking stock splits for {$equities->count()} equities...");
        $stats = $syncService->syncForEquities($equities, ['SPLIT']);

        $this->info("Splits sync complete: {$stats['saved']} split records saved/updated.");
        return self::SUCCESS;
    }
}
