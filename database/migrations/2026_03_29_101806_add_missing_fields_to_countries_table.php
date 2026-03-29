<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->decimal('population', 15, 2)->nullable()->after('native');
            $table->decimal('gdp', 15, 2)->nullable()->after('population');
            $table->text('timezones')->nullable()->after('postal_code_regex');
            $table->integer('max_mobile_digits')->nullable()->after('wiki_data_id');
            $table->string('international_prefix')->nullable()->after('max_mobile_digits');
            $table->string('trunk_prefix')->nullable()->after('international_prefix');
            $table->string('income_level')->nullable()->after('trunk_prefix');
            $table->boolean('is_oecd')->default(false)->after('income_level');
            $table->boolean('is_eu')->default(false)->after('is_oecd');
            $table->string('driving_side')->nullable()->after('is_eu');
            $table->string('measurement_system')->nullable()->after('driving_side');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn([
                'population',
                'gdp',
                'timezones',
                'max_mobile_digits',
                'international_prefix',
                'trunk_prefix',
                'income_level',
                'is_oecd',
                'is_eu',
                'driving_side',
                'measurement_system',
            ]);
        });
    }
};
