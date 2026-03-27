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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('coupon_id')->nullable()->after('plan_id')->constrained()->onDelete('set null');
            $table->decimal('discount_amount', 10, 2)->default(0)->after('amount_paid');
            $table->integer('remaining_discount_cycles')->default(0)->after('discount_amount');
        });
    }

    public function down()
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'discount_amount', 'remaining_discount_cycles']);
        });
    }
};
