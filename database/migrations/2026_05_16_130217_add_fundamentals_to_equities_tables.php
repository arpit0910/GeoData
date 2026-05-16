<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->string('series', 10)->nullable()->after('bse_symbol');
            $table->integer('market_lot')->nullable()->after('series');
            $table->string('status', 20)->nullable()->after('market_lot');
            $table->string('sector')->nullable()->after('industry');
            $table->string('basic_industry')->nullable()->after('sector');
            $table->json('index_membership')->nullable()->after('basic_industry');
            $table->string('company_website')->nullable()->after('company_name');
            $table->string('cin')->nullable()->after('company_website');
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->bigInteger('outstanding_shares')->nullable()->after('bse_val_3y');
            $table->decimal('market_cap', 15, 2)->nullable()->after('outstanding_shares');
            $table->decimal('pe_ratio', 10, 2)->nullable()->after('market_cap');
            $table->decimal('pb_ratio', 10, 2)->nullable()->after('pe_ratio');
            $table->decimal('dividend_yield', 8, 4)->nullable()->after('pb_ratio');
            $table->decimal('eps', 10, 2)->nullable()->after('dividend_yield');
        });
    }

    public function down(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->dropColumn([
                'series', 'market_lot', 'status', 'sector', 'basic_industry', 
                'index_membership', 'company_website', 'cin'
            ]);
        });

        Schema::table('equity_prices', function (Blueprint $table) {
            $table->dropColumn([
                'outstanding_shares', 'market_cap', 'pe_ratio', 
                'pb_ratio', 'dividend_yield', 'eps'
            ]);
        });
    }
};
