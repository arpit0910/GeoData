<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $aliases = [
            'sync:mf-daily (21:30)' => 'sync:mf-daily',
            'sync:mf-daily (23:15)' => 'sync:mf-daily',
            'mf:sync-and-calculate (23:30)' => 'mf:sync-and-calculate',
            'indices:sync (refresh holdings)' => 'indices:sync',
        ];

        foreach ($aliases as $old => $canonical) {
            DB::table('cron_logs')->where('title', $old)->update(['title' => $canonical]);
        }
    }

    public function down(): void
    {
        // Canonical titles cannot be reliably split back into their legacy schedule labels.
    }
};
