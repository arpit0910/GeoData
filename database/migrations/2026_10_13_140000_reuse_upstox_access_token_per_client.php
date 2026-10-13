<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('upstox_access_tokens')
            ->select('client_id')
            ->groupBy('client_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('client_id')
            ->each(function (string $clientId): void {
                $now = now()->toDateTimeString();
                $rows = DB::table('upstox_access_tokens')
                    ->where('client_id', $clientId)
                    ->orderByDesc('id')
                    ->get();

                $keeper = $rows->first(fn ($row) => $row->status === 'active'
                        && !empty($row->access_token)
                        && $row->expires_at > $now)
                    ?? $rows->first(fn ($row) => $row->status === 'pending'
                        && $row->authorization_expires_at > $now)
                    ?? $rows->firstWhere('status', 'active')
                    ?? $rows->first();

                DB::table('upstox_access_tokens')
                    ->where('client_id', $clientId)
                    ->where('id', '<>', $keeper->id)
                    ->delete();
            });

        Schema::table('upstox_access_tokens', function (Blueprint $table): void {
            $table->unique('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('upstox_access_tokens', function (Blueprint $table): void {
            $table->dropUnique(['client_id']);
        });
    }
};
