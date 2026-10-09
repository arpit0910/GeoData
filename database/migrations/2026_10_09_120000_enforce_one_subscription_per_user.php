<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $duplicates = DB::table('subscriptions')
            ->select('user_id')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $keeper = DB::table('subscriptions')
                ->where('user_id', $duplicate->user_id)
                ->orderByRaw(
                    "CASE WHEN status = 'active' AND (expires_at IS NULL OR expires_at > ?) THEN 0 WHEN status = 'active' THEN 1 ELSE 2 END",
                    [now()]
                )
                ->orderByDesc('id')
                ->first();

            $obsoleteIds = DB::table('subscriptions')
                ->where('user_id', $duplicate->user_id)
                ->where('id', '<>', $keeper->id)
                ->pluck('id');

            foreach (['transaction_histories', 'api_logs', 'coupon_user'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'subscription_id')) {
                    DB::table($table)
                        ->whereIn('subscription_id', $obsoleteIds)
                        ->update(['subscription_id' => $keeper->id]);
                }
            }

            DB::table('subscriptions')->whereIn('id', $obsoleteIds)->delete();

        }

        DB::table('subscriptions')->orderBy('id')->chunkById(500, function ($subscriptions): void {
            foreach ($subscriptions as $subscription) {
                $isAccessible = $subscription->status === 'active'
                    && ($subscription->expires_at === null || Carbon::parse($subscription->expires_at)->isFuture());

                DB::table('users')->where('id', $subscription->user_id)->update([
                    'plan_id' => $isAccessible ? $subscription->plan_id : null,
                    'available_credits' => $isAccessible
                        ? ($subscription->available_credits ?? 999999999)
                        : 0,
                ]);
            }
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unique('user_id', 'subscriptions_user_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropUnique('subscriptions_user_id_unique');
        });
    }
};
