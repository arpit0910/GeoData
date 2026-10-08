<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('market_news')
            ->where('editorial_status', 'failed')
            ->where(function ($query) {
                $query->where('rewrite_error', 'like', '%Rate limit reached%')
                    ->orWhere('rewrite_error', 'like', '%rate limit%')
                    ->orWhere('rewrite_error', 'like', '%output tokens per minute%');
            })
            ->update([
                'editorial_status' => 'pending',
                'rewrite_retry_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Requeued articles must not be changed back to failed on rollback.
    }
};
