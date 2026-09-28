<?php

namespace Tests\Feature\Admin;

use App\Models\Equity;
use App\Models\User;
use App\Services\EquityQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquityQuoteAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_latest_quotes_page_displays_nse_and_bse_in_one_isin_row(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Equity::create([
            'isin' => 'INE002A01018',
            'company_name' => 'Reliance Industries',
            'nse_symbol' => 'RELIANCE',
            'bse_symbol' => '500325',
            'series' => 'EQ',
            'is_active' => true,
        ]);

        $quotes = app(EquityQuoteService::class);
        $quotes->store('INE002A01018', 'NSE', $this->quote('RELIANCE', 125.50));
        $quotes->store('INE002A01018', 'BSE', $this->quote('500325', 125.75));

        $this->actingAs($admin)
            ->get(route('equities.quotes'))
            ->assertOk()
            ->assertSee('Reliance Industries')
            ->assertSee('RELIANCE')
            ->assertSee('500325')
            ->assertSee('125.50')
            ->assertSee('125.75');
    }

    private function quote(string $symbol, float $price): array
    {
        return [
            'symbol' => $symbol,
            'price' => $price,
            'previous_close' => 124.00,
            'dp' => 1.2,
            'currency' => 'INR',
            'quoted_at' => now()->utc()->toIso8601String(),
            'fetched_at' => now()->utc()->toIso8601String(),
            'source' => 'live',
            'provider' => 'test',
        ];
    }
}
