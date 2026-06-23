<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        Schema::create('benefit_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('benefit_id')->constrained('benefits')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['benefit_id', 'plan_id']);
        });

        $plans = DB::table('plans')->select('id', 'benefits')->get();

        foreach ($plans as $plan) {
            $benefitNames = json_decode($plan->benefits ?? '[]', true);

            if (!is_array($benefitNames)) {
                continue;
            }

            foreach (array_values(array_filter($benefitNames)) as $index => $benefitName) {
                $benefitId = DB::table('benefits')->where('name', $benefitName)->value('id');

                if (!$benefitId) {
                    $benefitId = DB::table('benefits')->insertGetId([
                        'name' => $benefitName,
                        'slug' => Str::slug($benefitName) . '-' . Str::lower(Str::random(6)),
                        'description' => $benefitName,
                        'is_active' => true,
                        'sort_order' => $index,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('benefit_plan')->updateOrInsert([
                    'benefit_id' => $benefitId,
                    'plan_id' => $plan->id,
                ], [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down()
    {
        Schema::dropIfExists('benefit_plan');
    }
};
