<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Retain NSE when available; otherwise retain the newest BSE row.
        $keepIds = DB::table('equity_quotes')
            ->orderBy('isin')
            ->orderByRaw("CASE WHEN exchange = 'NSE' THEN 0 ELSE 1 END")
            ->orderByDesc('quoted_at')
            ->get(['id', 'isin'])
            ->unique('isin')
            ->pluck('id');

        if ($keepIds->isNotEmpty()) {
            DB::table('equity_quotes')->whereNotIn('id', $keepIds->all())->delete();
        }

        Schema::table('equity_quotes', function (Blueprint $table) {
            $table->dropUnique('equity_quotes_isin_exchange_unique');
            $table->unique('isin');
        });
    }

    public function down(): void
    {
        Schema::table('equity_quotes', function (Blueprint $table) {
            $table->dropUnique('equity_quotes_isin_unique');
            $table->unique(['isin', 'exchange']);
        });
    }
};
