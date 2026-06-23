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
        Schema::create('plan_subscription_feature', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->foreignId('subscription_feature_id')->constrained('subscription_features')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['plan_id', 'subscription_feature_id'], 'plan_feature_unique');
        });

        $allApiFeatureId = DB::table('subscription_features')
            ->where('key', SubscriptionFeature::MODULE_ALL_API)
            ->value('id');

        if ($allApiFeatureId) {
            $timestamp = now();
            $planRows = DB::table('plans')->pluck('id')->map(function ($planId) use ($allApiFeatureId, $timestamp) {
                return [
                    'plan_id' => $planId,
                    'subscription_feature_id' => $allApiFeatureId,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })->all();

            if ($planRows !== []) {
                DB::table('plan_subscription_feature')->insert($planRows);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('plan_subscription_feature');
    }
};
