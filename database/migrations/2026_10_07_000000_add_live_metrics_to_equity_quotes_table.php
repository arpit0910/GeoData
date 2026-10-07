<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $existingColumns = Schema::getColumnListing('equity_quotes');
        Schema::table('equity_quotes', function (Blueprint $table) use ($existingColumns) {
            if (! in_array('previous_close', $existingColumns, true)) {
                $table->decimal('previous_close', 20, 4)->nullable()->after('price');
            }
            if (! in_array('change', $existingColumns, true)) {
                $table->decimal('change', 20, 4)->nullable()->after('previous_close');
            }
            if (! in_array('change_percent', $existingColumns, true)) {
                $table->decimal('change_percent', 12, 4)->nullable()->after('change');
            }
            if (! in_array('live_volume', $existingColumns, true)) {
                $table->unsignedBigInteger('live_volume')->nullable()->after('change_percent');
            }
        });

        DB::table('equity_quotes')->orderBy('id')->chunkById(500, function ($quotes) {
            $updates = [];
            foreach ($quotes as $quote) {
                $payload = json_decode((string) $quote->payload, true);
                if (! is_array($payload)) {
                    continue;
                }

                $updates[] = [
                    'id' => $quote->id,
                    // Include every non-null legacy column so MySQL can
                    // validate the INSERT side of INSERT ... ON DUPLICATE KEY.
                    // Only the four derived columns are changed on conflict.
                    'isin' => $quote->isin,
                    'exchange' => $quote->exchange,
                    'symbol' => $quote->symbol,
                    'price' => $quote->price,
                    'quoted_at' => $quote->quoted_at,
                    'fetched_at' => $quote->fetched_at,
                    'payload' => $quote->payload,
                    'previous_close' => $this->numeric($payload['previous_close'] ?? null),
                    'change' => $this->numeric($payload['d'] ?? null),
                    'change_percent' => $this->numeric($payload['dp'] ?? null),
                    'live_volume' => $this->integer($payload['volume'] ?? data_get($payload, 'market_data.volume')),
                ];
            }

            if ($updates !== []) {
                DB::table('equity_quotes')->upsert(
                    $updates,
                    ['id'],
                    ['previous_close', 'change', 'change_percent', 'live_volume']
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('equity_quotes', function (Blueprint $table) {
            $table->dropColumn(['previous_close', 'change', 'change_percent', 'live_volume']);
        });
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function integer(mixed $value): ?int
    {
        return is_numeric($value) && (float) $value >= 0 ? (int) $value : null;
    }
};
