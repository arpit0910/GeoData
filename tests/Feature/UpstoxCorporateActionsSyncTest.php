<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class UpstoxCorporateActionsSyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.corporate_actions_url' => 'https://provider.test/v2/fundamentals',
        ]);
    }

    public function test_it_syncs_every_action_type_and_all_documented_event_fields(): void
    {
        $this->equity('INE002A01018', 'Reliance Industries');

        Http::fake(['https://provider.test/*' => Http::response([
            'status' => 'success',
            'data' => [
                $this->action('Dividend', '14 Aug 2026', 5.5, null),
                $this->action('Bonus Issue', '15 Sep 2026', null, '1:1'),
                $this->action('Stock Split', '16 Oct 2026', null, '2:1'),
                $this->action('Rights Issue', '17 Nov 2026', null, '3:10'),
            ],
        ])]);

        $this->artisan('market:sync-upstox-events', ['--all' => true, '--delay' => 0])
            ->expectsOutputToContain('4 actions fetched, 4 saved/updated, 0 failed')
            ->assertExitCode(0);

        $this->assertSame(
            ['BONUS', 'DIVIDEND', 'RIGHTS', 'SPLIT'],
            DB::table('corporate_actions')->orderBy('type')->pluck('type')->all()
        );
        $this->assertDatabaseHas('corporate_actions', [
            'isin' => 'INE002A01018',
            'type' => 'DIVIDEND',
            'expiry_date' => '2026-08-14 00:00:00',
            'record_date' => '2026-08-15 00:00:00',
            'announcement_date' => '2026-08-01 00:00:00',
            'amount' => 5.5,
        ]);
        $this->assertNotNull(DB::table('equities')->value('corporate_actions_synced_at'));
        $this->assertNull(DB::table('equities')->value('corporate_actions_sync_error'));

        $this->artisan('market:sync-upstox-events', ['--all' => true, '--delay' => 0])
            ->assertExitCode(0);
        $this->assertDatabaseCount('corporate_actions', 4);
    }

    public function test_rotating_batches_cover_equities_without_upstox_instrument_keys(): void
    {
        foreach (range(1, 3) as $index) {
            $this->equity(sprintf('INE00000%d00%d', $index, $index), 'Company '.$index);
        }
        Http::fake(fn () => Http::response(['status' => 'success', 'data' => []]));

        $this->artisan('market:sync-upstox-events', ['--limit' => 2, '--delay' => 0])
            ->assertExitCode(0);
        $this->assertSame(2, DB::table('equities')->whereNotNull('corporate_actions_synced_at')->count());

        $this->artisan('market:sync-upstox-events', ['--limit' => 2, '--delay' => 0])
            ->assertExitCode(0);
        $this->assertSame(3, DB::table('equities')->whereNotNull('corporate_actions_synced_at')->count());

        $requestedIsins = collect(Http::recorded())->map(function (array $recorded) {
            $segments = explode('/', trim((string) parse_url($recorded[0]->url(), PHP_URL_PATH), '/'));

            return $segments[count($segments) - 2] ?? null;
        });
        $this->assertCount(3, $requestedIsins->unique());
    }

    public function test_provider_failures_are_retried_reported_and_not_marked_successful(): void
    {
        $this->equity('INE002A01018', 'Reliance Industries');
        Http::fake(['https://provider.test/*' => Http::response([
            'status' => 'error',
            'errors' => [['message' => 'Service temporarily unavailable']],
        ], 503)]);

        $this->artisan('market:sync-upstox-events', ['--all' => true, '--delay' => 0])
            ->expectsOutputToContain('1 failed')
            ->assertExitCode(1);

        Http::assertSentCount(3);
        $equity = DB::table('equities')->first();
        $this->assertNotNull($equity->corporate_actions_sync_attempted_at);
        $this->assertNull($equity->corporate_actions_synced_at);
        $this->assertStringContainsString('HTTP 503', $equity->corporate_actions_sync_error);
    }

    private function equity(string $isin, string $name): void
    {
        DB::table('equities')->insert([
            'isin' => $isin,
            'company_name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @return array<string, mixed> */
    private function action(string $name, string $expiryDate, ?float $amount, ?string $ratio): array
    {
        return [
            'name' => $name,
            'expiry_date' => $expiryDate,
            'amount' => $amount,
            'ratio' => $ratio,
            'event_details' => [
                ['name' => 'ANNOUNCEMENT DATE', 'value' => '01 Aug 2026'],
                ['name' => 'Ex Date', 'value' => $expiryDate],
                ['name' => 'Record date', 'value' => '15 Aug 2026'],
                ['name' => 'Details', 'value' => $name.' details'],
            ],
        ];
    }
}
