<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->string('short_name')->nullable();
            $table->string('security_type', 50)->nullable();
            $table->text('company_profile')->nullable();
            $table->string('nse_exchange_token', 100)->nullable();
            $table->string('bse_exchange_token', 100)->nullable();
            $table->decimal('nse_tick_size', 12, 4)->nullable();
            $table->decimal('bse_tick_size', 12, 4)->nullable();
            $table->decimal('nse_freeze_quantity', 20, 4)->nullable();
            $table->decimal('bse_freeze_quantity', 20, 4)->nullable();
            $table->decimal('qty_multiplier', 20, 4)->nullable();
            $table->boolean('mtf_enabled')->nullable();
            $table->decimal('mtf_bracket', 12, 4)->nullable();
            $table->boolean('cas_eligible')->nullable();
            $table->decimal('intraday_margin', 12, 4)->nullable();
            $table->decimal('intraday_leverage', 12, 4)->nullable();
            $table->json('upstox_nse_metadata')->nullable();
            $table->json('upstox_bse_metadata')->nullable();
            $table->timestamp('upstox_synced_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->dropColumn([
                'short_name', 'security_type', 'company_profile',
                'nse_exchange_token', 'bse_exchange_token',
                'nse_tick_size', 'bse_tick_size',
                'nse_freeze_quantity', 'bse_freeze_quantity',
                'qty_multiplier', 'mtf_enabled', 'mtf_bracket', 'cas_eligible',
                'intraday_margin', 'intraday_leverage',
                'upstox_nse_metadata', 'upstox_bse_metadata', 'upstox_synced_at',
            ]);
        });
    }
};
