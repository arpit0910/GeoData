<?php

namespace App\Console\Commands;

use App\Models\Equity;
use App\Services\UpstoxCorporateActionSyncService;
use App\Services\UpstoxTokenManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncUpstoxEventsCommand extends Command
{
    protected $signature = 'market:sync-upstox-events
        {--limit=100 : Number of equities in a rotating scheduled batch}
        {--all : Synchronize every eligible equity}
        {--delay=200 : Delay in milliseconds between Upstox requests}
        {--isin=* : Specific ISINs to sync}';

    protected $description = 'Sync all corporate actions from Upstox with deterministic coverage and retry tracking';

    public function handle(UpstoxCorporateActionSyncService $syncService, UpstoxTokenManager $tokens): int
    {
        try {
            $tokens->accessToken();
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $limit = max(1, min((int) $this->option('limit'), 5000));
        $delayMs = max(0, min((int) $this->option('delay'), 5000));

        $query = Equity::query()
            ->where('is_active', true)
            ->whereNotNull('isin')
            ->where('isin', '<>', '');

        $isinFilter = collect($this->option('isin'))
            ->map(fn ($isin) => strtoupper(trim((string) $isin)))
            ->filter()
            ->unique();

        if ($isinFilter->isNotEmpty()) {
            $query->whereIn('isin', $isinFilter->all());
        } else {
            if (Schema::hasColumn('equities', 'corporate_actions_sync_attempted_at')) {
                $query
                    ->orderByRaw('corporate_actions_sync_attempted_at IS NULL DESC')
                    ->orderBy('corporate_actions_sync_attempted_at');
            }

            $query->orderBy('id');

            if (!$this->option('all')) {
                $query->limit($limit);
            }
        }

        $equities = $query->get();

        if ($equities->isEmpty()) {
            $this->warn('No eligible equities found.');
            return self::SUCCESS;
        }

        $this->info("Syncing every corporate-action type for {$equities->count()} equities...");
        $stats = $syncService->syncForEquities($equities, null, $delayMs);

        foreach ($stats['messages'] as $message) {
            $this->warn($message);
        }

        $this->info(
            "Corporate actions sync complete: {$stats['companies']} companies checked, "
            ."{$stats['fetched']} actions fetched, {$stats['saved']} saved/updated, {$stats['errors']} failed."
        );

        return $stats['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
