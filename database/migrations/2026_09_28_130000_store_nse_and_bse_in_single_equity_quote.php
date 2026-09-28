<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equity_quotes', function (Blueprint $table) {
            $table->string('nse_symbol')->nullable();
            $table->decimal('nse_price', 20, 4)->nullable();
            $table->timestamp('nse_quoted_at')->nullable();
            $table->timestamp('nse_fetched_at')->nullable();
            $table->json('nse_payload')->nullable();
            $table->string('bse_symbol')->nullable();
            $table->decimal('bse_price', 20, 4)->nullable();
            $table->timestamp('bse_quoted_at')->nullable();
            $table->timestamp('bse_fetched_at')->nullable();
            $table->json('bse_payload')->nullable();
        });

        DB::table('equity_quotes')
            ->orderBy('id')
            ->chunkById(500, function ($quotes) {
                foreach ($quotes as $quote) {
                    $prefix = strtoupper((string) $quote->exchange) === 'BSE' ? 'bse' : 'nse';

                    DB::table('equity_quotes')->where('id', $quote->id)->update([
                        "{$prefix}_symbol" => $quote->symbol,
                        "{$prefix}_price" => $quote->price,
                        "{$prefix}_quoted_at" => $quote->quoted_at,
                        "{$prefix}_fetched_at" => $quote->fetched_at,
                        "{$prefix}_payload" => $quote->payload,
                    ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('equity_quotes', function (Blueprint $table) {
            $table->dropColumn([
                'nse_symbol',
                'nse_price',
                'nse_quoted_at',
                'nse_fetched_at',
                'nse_payload',
                'bse_symbol',
                'bse_price',
                'bse_quoted_at',
                'bse_fetched_at',
                'bse_payload',
            ]);
        });
    }
};
