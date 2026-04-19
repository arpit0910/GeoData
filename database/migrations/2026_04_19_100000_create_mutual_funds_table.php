<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mutual_funds', function (Blueprint $table) {
            $table->string('isin', 12)->primary();
            $table->string('scheme_code', 20)->unique();          // mfapi.in lookup key
            $table->string('isin_reinvest', 12)->nullable();      // IDCW reinvestment ISIN from AMFI
            $table->string('scheme_name', 300);
            $table->string('amc_name', 150)->nullable()->index();
            $table->string('category', 100)->nullable()->index(); // Equity, Debt, Hybrid, …
            $table->string('sub_category', 100)->nullable();
            $table->string('type', 50)->nullable()->index();      // Growth, IDCW
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        // FULLTEXT index only on MySQL — skipped for SQLite compatibility
    }

    public function down()
    {
        Schema::dropIfExists('mutual_funds');
    }
};
