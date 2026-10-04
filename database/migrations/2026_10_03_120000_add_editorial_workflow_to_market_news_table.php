<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('market_news', function (Blueprint $table) {
            $table->string('original_title', 500)->nullable()->after('instrument_key');
            $table->text('original_summary')->nullable()->after('original_title');
            $table->string('source_hash', 64)->nullable()->index()->after('original_summary');
            $table->string('editorial_status', 20)->default('pending')->index()->after('raw_data');
            $table->boolean('is_published')->default(false)->index()->after('editorial_status');
            $table->string('rewrite_model', 100)->nullable()->after('is_published');
            $table->text('rewrite_error')->nullable()->after('rewrite_model');
            $table->dateTime('rewritten_at')->nullable()->after('rewrite_error');
            $table->dateTime('reviewed_at')->nullable()->after('rewritten_at');
            $table->unsignedBigInteger('reviewed_by')->nullable()->index()->after('reviewed_at');
        });

        DB::table('market_news')->orderBy('id')->chunkById(100, function ($rows) {
            foreach ($rows as $row) {
                DB::table('market_news')->where('id', $row->id)->update([
                    'original_title' => $row->title,
                    'original_summary' => $row->summary,
                    'source_hash' => hash('sha256', $row->title."\n".($row->summary ?? '')),
                    'editorial_status' => 'pending',
                    'is_published' => false,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('market_news', function (Blueprint $table) {
            $table->dropColumn([
                'original_title', 'original_summary', 'source_hash', 'editorial_status',
                'is_published', 'rewrite_model', 'rewrite_error', 'rewritten_at',
                'reviewed_at', 'reviewed_by',
            ]);
        });
    }
};
