<?php

use App\Models\SubscriptionFeature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_features')) {
            return;
        }

        $features = [
            [SubscriptionFeature::MODULE_INDIA_PINCODE_API, 'India Pincode API', 'Single-object Indian pincode detail lookup.'],
            [SubscriptionFeature::MODULE_IFSC_API, 'IFSC API', 'Single bank branch lookup by IFSC code.'],
        ];

        foreach ($features as [$key, $name, $description]) {
            DB::table('subscription_features')->updateOrInsert(
                ['key' => $key],
                ['name' => $name, 'description' => $description, 'is_active' => true, 'updated_at' => now(), 'created_at' => now()]
            );
        }

        // Existing banking plans must retain their IFSC access after the route
        // is moved to the narrower feature gate.
        if (Schema::hasTable('plans') && Schema::hasTable('plan_subscription_feature')) {
            $ifscId = DB::table('subscription_features')->where('key', SubscriptionFeature::MODULE_IFSC_API)->value('id');
            $bankingPlanIds = DB::table('plan_subscription_feature as pivot')
                ->join('subscription_features as feature', 'feature.id', '=', 'pivot.subscription_feature_id')
                ->where('feature.key', SubscriptionFeature::MODULE_BANKING_CURRENCY_API)
                ->pluck('pivot.plan_id');

            foreach ($bankingPlanIds as $planId) {
                DB::table('plan_subscription_feature')->updateOrInsert(
                    ['plan_id' => $planId, 'subscription_feature_id' => $ifscId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_features')) {
            return;
        }

        $ids = DB::table('subscription_features')
            ->whereIn('key', [SubscriptionFeature::MODULE_INDIA_PINCODE_API, SubscriptionFeature::MODULE_IFSC_API])
            ->pluck('id');

        if (Schema::hasTable('plan_subscription_feature')) {
            DB::table('plan_subscription_feature')->whereIn('subscription_feature_id', $ids)->delete();
        }
        DB::table('subscription_features')->whereIn('id', $ids)->delete();
    }
};
