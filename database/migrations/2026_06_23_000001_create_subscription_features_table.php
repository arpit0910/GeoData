<?php

use App\Models\SubscriptionFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_features', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('subscription_features')->insert([
            [
                'key' => SubscriptionFeature::MODULE_ADDRESS_API,
                'name' => 'Address API',
                'description' => 'Pincode, address, city/state, and location API access.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => SubscriptionFeature::MODULE_BANKING_CURRENCY_API,
                'name' => 'Banking & Currency API',
                'description' => 'IFSC, bank branch, and currency API access.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => SubscriptionFeature::MODULE_STOCKS_MUTUAL_FUNDS_API,
                'name' => 'Stocks & Mutual Funds API',
                'description' => 'Stocks, mutual funds, NAV, and market API access.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => SubscriptionFeature::MODULE_ALL_API,
                'name' => 'All API Access',
                'description' => 'Access to all current and future API modules.',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('subscription_features');
    }
};
