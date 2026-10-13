<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_nudges', function (Blueprint $table) {
            $table->string('isin', 20)->nullable()->change();
            $table->string('index_code')->nullable()->after('isin');
            $table->foreign('index_code')->references('index_code')->on('indices')->nullOnDelete();
            $table->index(['index_code', 'trading_date']);
        });
    }

    public function down(): void
    {
        DB::table('stock_nudges')->whereNotNull('index_code')->delete();

        Schema::table('stock_nudges', function (Blueprint $table) {
            $table->dropForeign(['index_code']);
            $table->dropIndex(['index_code', 'trading_date']);
            $table->dropColumn('index_code');
            $table->string('isin', 20)->nullable(false)->change();
        });
    }
};
