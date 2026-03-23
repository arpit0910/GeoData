<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable();
            }
            if (!Schema::hasColumn('users', 'company_name')) {
                $table->string('company_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'company_website')) {
                $table->string('company_website')->nullable();
            }
            if (!Schema::hasColumn('users', 'gst_number')) {
                $table->string('gst_number')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('users', 'phone')) $columnsToDrop[] = 'phone';
            if (Schema::hasColumn('users', 'company_name')) $columnsToDrop[] = 'company_name';
            if (Schema::hasColumn('users', 'company_website')) $columnsToDrop[] = 'company_website';
            if (Schema::hasColumn('users', 'gst_number')) $columnsToDrop[] = 'gst_number';

            if (count($columnsToDrop) > 0) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
