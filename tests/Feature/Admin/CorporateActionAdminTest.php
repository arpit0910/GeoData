<?php

namespace Tests\Feature\Admin;

use App\Models\CorporateAction;
use App\Models\Equity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CorporateActionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_monitor_filter_and_view_corporate_actions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $equity = Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'is_active' => true,
        ]);
        $equity->forceFill([
            'corporate_actions_sync_attempted_at' => now(),
            'corporate_actions_synced_at' => now(),
        ])->save();
        $action = CorporateAction::create([
            'isin' => $equity->isin,
            'symbol' => $equity->nse_symbol,
            'company_name' => $equity->company_name,
            'type' => 'DIVIDEND',
            'name' => 'Final Dividend',
            'expiry_date' => '2026-10-20',
            'record_date' => '2026-10-21',
            'announcement_date' => '2026-10-01',
            'amount' => 5.5,
            'details' => 'Rs. 5.50 per share final dividend.',
        ]);
        $failedEquity = Equity::create([
            'isin' => 'INE009A01021',
            'company_name' => 'Failed Company',
            'is_active' => true,
        ]);
        $failedEquity->forceFill([
            'corporate_actions_sync_attempted_at' => now(),
            'corporate_actions_sync_error' => 'Provider temporarily unavailable.',
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.corporate-actions.index', ['type' => 'DIVIDEND', 'search' => 'Reliance']))
            ->assertOk()
            ->assertSee('Corporate Actions')
            ->assertSee('Reliance Industries')
            ->assertSee('Final Dividend')
            ->assertSee('Successfully checked')
            ->assertSee('Recent sync failures')
            ->assertSee('Provider temporarily unavailable.');

        $this->actingAs($admin)
            ->get(route('admin.corporate-actions.show', $action))
            ->assertOk()
            ->assertSee('Corporate Action Details')
            ->assertSee('Rs. 5.50 per share final dividend.');

        $this->actingAs($admin)->view('dashboard')
            ->assertSee('Corporate Actions')
            ->assertSee('Final Dividend')
            ->assertSee(route('admin.corporate-actions.index'), false);
    }

    public function test_admin_can_sync_an_isin_and_see_the_new_action(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'is_active' => true,
        ]);
        config([
            'market_data.upstox.access_token' => 'test-access-token',
            'market_data.upstox.corporate_actions_url' => 'https://provider.test/v2/fundamentals',
        ]);
        Http::fake(['https://provider.test/*' => Http::response([
            'status' => 'success',
            'data' => [[
                'name' => 'Stock Split',
                'expiry_date' => '20 Oct 2026',
                'ratio' => '2:1',
                'event_details' => [
                    ['name' => 'Announcement date', 'value' => '01 Oct 2026'],
                    ['name' => 'Record date', 'value' => '21 Oct 2026'],
                    ['name' => 'Details', 'value' => 'Face value split in the ratio 2:1.'],
                ],
            ]],
        ])]);

        $this->actingAs($admin)
            ->post(route('admin.corporate-actions.sync'), ['isin' => 'INE002A01018'])
            ->assertRedirect(route('admin.corporate-actions.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('corporate_actions', [
            'isin' => 'INE002A01018',
            'type' => 'SPLIT',
            'ratio' => '2:1',
        ]);
        $this->actingAs($admin)->get(route('admin.corporate-actions.index'))
            ->assertOk()
            ->assertSee('Stock Split');
    }

    public function test_non_admin_cannot_view_or_run_corporate_action_sync(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get(route('admin.corporate-actions.index'))->assertRedirect('/');
        $this->actingAs($user)->post(route('admin.corporate-actions.sync'), ['limit' => 25])->assertRedirect('/');
    }

    public function test_admin_page_remains_available_before_sync_tracking_migration_is_applied(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        Schema::table('equities', function (Blueprint $table) {
            $table->dropColumn([
                'corporate_actions_sync_attempted_at',
                'corporate_actions_synced_at',
                'corporate_actions_sync_error',
            ]);
        });

        $this->actingAs($admin)
            ->get(route('admin.corporate-actions.index'))
            ->assertOk()
            ->assertSee('Synchronization tracking is awaiting the latest database migration.');
    }
}
