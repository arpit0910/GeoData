<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        $addPhone = !Schema::hasColumn('users', 'phone');
        $addCompanyName = !Schema::hasColumn('users', 'company_name');
        $addCompanyWebsite = !Schema::hasColumn('users', 'company_website');
        $addGstNumber = !Schema::hasColumn('users', 'gst_number');

        if (!$addPhone && !$addCompanyName && !$addCompanyWebsite && !$addGstNumber) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($addPhone, $addCompanyName, $addCompanyWebsite, $addGstNumber) {
            if ($addPhone) {
                $table->string('phone', 20)->nullable();
            }
            if ($addCompanyName) {
                $table->string('company_name')->nullable();
            }
            if ($addCompanyWebsite) {
                $table->string('company_website')->nullable();
            }
            if ($addGstNumber) {
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
