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
        // MySQL TIMESTAMP ends in 2038, which is too short for tokens issued
        // later in this ten-year lifecycle. DATETIME supports the full range.
        Schema::table('upstox_access_tokens', function (Blueprint $table): void {
            $table->dateTime('expires_at')->nullable()->change();
        });

        $lifetimeYears = max(1, (int) config('market_data.upstox.token_lifetime_years', 10));

        DB::table('upstox_access_tokens')
            ->where('status', 'active')
            ->whereNotNull('issued_at')
            ->orderBy('id')
            ->chunkById(100, function ($tokens) use ($lifetimeYears): void {
                foreach ($tokens as $token) {
                    $issuedAt = Carbon::parse($token->issued_at);
                    $maximumExpiry = $issuedAt->copy()->addYears($lifetimeYears);
                    $metadata = json_decode((string) $token->metadata, true) ?: [];
                    $providerExpiryValue = data_get($metadata, 'provider_expires_at');
                    $providerExpiry = $providerExpiryValue
                        ? Carbon::parse($providerExpiryValue)
                        : null;
                    $effectiveExpiry = $providerExpiry && $providerExpiry->lessThan($maximumExpiry)
                        ? $providerExpiry
                        : $maximumExpiry;

                    DB::table('upstox_access_tokens')
                        ->where('id', $token->id)
                        ->update(['expires_at' => $effectiveExpiry]);
                }
            });
    }

    public function down(): void
    {
        // Token expiry cannot be safely shortened after it has been extended.
    }
};
