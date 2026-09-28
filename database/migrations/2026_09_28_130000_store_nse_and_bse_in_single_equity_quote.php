<?php

use Carbon\Carbon;
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
            if (!in_array('nse_symbol', $existingColumns, true)) {
                $table->string('nse_symbol')->nullable();
            }
            if (!in_array('nse_price', $existingColumns, true)) {
                $table->decimal('nse_price', 20, 4)->nullable();
            }
            if (!in_array('nse_quoted_at', $existingColumns, true)) {
                $table->timestamp('nse_quoted_at')->nullable();
            }
            if (!in_array('nse_fetched_at', $existingColumns, true)) {
                $table->timestamp('nse_fetched_at')->nullable();
            }
            if (!in_array('nse_payload', $existingColumns, true)) {
                $table->json('nse_payload')->nullable();
            }
            if (!in_array('bse_symbol', $existingColumns, true)) {
                $table->string('bse_symbol')->nullable();
            }
            if (!in_array('bse_price', $existingColumns, true)) {
                $table->decimal('bse_price', 20, 4)->nullable();
            }
            if (!in_array('bse_quoted_at', $existingColumns, true)) {
                $table->timestamp('bse_quoted_at')->nullable();
            }
            if (!in_array('bse_fetched_at', $existingColumns, true)) {
                $table->timestamp('bse_fetched_at')->nullable();
            }
            if (!in_array('bse_payload', $existingColumns, true)) {
                $table->json('bse_payload')->nullable();
            }
        });

        DB::table('equity_quotes')
            ->orderBy('id')
            ->chunkById(500, function ($quotes) {
                foreach ($quotes as $quote) {
                    $prefix = strtoupper((string) $quote->exchange) === 'BSE' ? 'bse' : 'nse';
                    $payload = json_decode((string) $quote->payload, true);
                    $payload = is_array($payload) ? $payload : [];
                    $storedFetchedAt = $this->validTimestamp($quote->fetched_at);
                    $storedQuotedAt = $this->validTimestamp($quote->quoted_at);
                    $fetchedAt = $storedFetchedAt
                        ?? $this->validTimestamp($payload['fetched_at'] ?? null)
                        ?? now()->utc()->format('Y-m-d H:i:s');
                    $quotedAt = $storedQuotedAt
                        ?? $this->validTimestamp($payload['quoted_at'] ?? null)
                        ?? $fetchedAt;

                    $updates = [
                        "{$prefix}_symbol" => $quote->symbol,
                        "{$prefix}_price" => $quote->price,
                        "{$prefix}_quoted_at" => $quotedAt,
                        "{$prefix}_fetched_at" => $fetchedAt,
                        "{$prefix}_payload" => $quote->payload,
                    ];

                    // Repair legacy zero-dates too, otherwise later reads and
                    // preferred-quote updates can fail under strict SQL mode.
                    if ($storedQuotedAt === null) {
                        $updates['quoted_at'] = $quotedAt;
                    }
                    if ($storedFetchedAt === null) {
                        $updates['fetched_at'] = $fetchedAt;
                    }

                    DB::table('equity_quotes')->where('id', $quote->id)->update($updates);
                }
            });
    }

    public function down(): void
    {
        $columns = array_values(array_intersect(
            Schema::getColumnListing('equity_quotes'),
            [
                'nse_symbol', 'nse_price', 'nse_quoted_at', 'nse_fetched_at', 'nse_payload',
                'bse_symbol', 'bse_price', 'bse_quoted_at', 'bse_fetched_at', 'bse_payload',
            ]
        ));

        if ($columns !== []) {
            Schema::table('equity_quotes', fn (Blueprint $table) => $table->dropColumn($columns));
        }
    }

    private function validTimestamp(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, '0000-00-00')) {
            return null;
        }

        try {
            $date = Carbon::parse($value, 'UTC')->utc();

            // 1970 is the provider's placeholder for an absent quote time.
            return $date->year >= 2000 ? $date->format('Y-m-d H:i:s') : null;
        } catch (\Throwable) {
            return null;
        }
    }
};
