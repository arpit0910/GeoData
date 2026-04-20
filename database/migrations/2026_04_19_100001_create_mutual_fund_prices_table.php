<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutual_fund_prices', function (Blueprint $table) {
            $table->id();
            $table->string('isin', 12);
            $table->date('nav_date');
            $table->decimal('nav', 15, 4);
            $table->decimal('chg_1d', 10, 4)->nullable();
            $table->decimal('val_1d', 15, 4)->nullable();
            $table->decimal('chg_3d', 10, 4)->nullable();
            $table->decimal('val_3d', 15, 4)->nullable();
            $table->decimal('chg_7d', 10, 4)->nullable();
            $table->decimal('val_7d', 15, 4)->nullable();
            $table->decimal('chg_1m', 10, 4)->nullable();
            $table->decimal('val_1m', 15, 4)->nullable();
            $table->decimal('chg_3m', 10, 4)->nullable();
            $table->decimal('val_3m', 15, 4)->nullable();
            $table->decimal('chg_6m', 10, 4)->nullable();
            $table->decimal('val_6m', 15, 4)->nullable();
            $table->decimal('chg_9m', 10, 4)->nullable();
            $table->decimal('val_9m', 15, 4)->nullable();
            $table->decimal('chg_1y', 10, 4)->nullable();
            $table->decimal('val_1y', 15, 4)->nullable();
            $table->decimal('chg_3y', 10, 4)->nullable();
            $table->decimal('val_3y', 15, 4)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index('nav_date');
            $table->foreign('isin')->references('isin')->on('mutual_funds')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutual_fund_prices');
    }
};
