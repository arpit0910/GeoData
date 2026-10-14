<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->string('logo_url')->nullable()->after('company_website');
        });
    }

    public function down(): void
    {
        Schema::table('equities', function (Blueprint $table) {
            $table->dropColumn('logo_url');
        });
    }
};
