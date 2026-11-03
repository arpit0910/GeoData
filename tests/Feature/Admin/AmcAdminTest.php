<?php

namespace Tests\Feature\Admin;

use App\Models\Amc;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AmcAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_browse_and_search_amcs_with_favicons_and_scheme_counts(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $amc = Amc::create(['amfi_id' => 53, 'name' => 'Axis Mutual Fund', 'company_name' => 'Axis Asset Management',
            'website' => 'www.axismf.com', 'logo_path' => 'assets/amc-favicons/53.svg', 'synced_at' => now()]);
        Amc::create(['amfi_id' => 54, 'name' => 'Other Mutual Fund']);
        DB::table('mutual_funds')->insert(['isin' => 'INF000000001', 'scheme_code' => '123456',
            'scheme_name' => 'Axis Growth Fund', 'amc_name' => 'Axis Mutual Fund']);

        $this->actingAs($admin)->get(route('admin.amcs.index'))->assertOk()
            ->assertSee('Axis Mutual Fund')->assertSee('Other Mutual Fund')
            ->assertSee($amc->favicon_url)->assertSee('https://www.axismf.com')
            ->assertSee('Sync AMC details')->assertSee(route('admin.amcs.show', $amc))
            ->assertViewHas('amcs', fn ($rows) => $rows->first()->funds_count === 1);
        $this->get(route('admin.amcs.index', ['search' => 'Asset Management']))->assertOk()
            ->assertSee('Axis Mutual Fund')->assertDontSee('Other Mutual Fund');
        $this->get(route('admin.amcs.index', ['search' => '53']))->assertOk()
            ->assertSee('Axis Mutual Fund')->assertDontSee('Other Mutual Fund');
        $this->get(route('admin.amcs.index', ['favicon' => 'missing']))->assertOk()
            ->assertSee('Other Mutual Fund')->assertDontSee('Axis Mutual Fund');
        $this->get(route('admin.amcs.index', ['favicon' => 'available']))->assertOk()
            ->assertSee('Axis Mutual Fund')->assertDontSee('Other Mutual Fund');
    }

    public function test_details_show_saved_information_and_escape_untrusted_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $amc = Amc::create(['amfi_id' => 53, 'name' => 'Example Mutual Fund',
            'website' => 'javascript:alert(1)',
            'details' => ['amc_address' => 'Mumbai', 'contacts' => ['email' => 'hello@example.test'], 'notes' => '<script>alert(1)</script>']]);
        $this->actingAs($admin)->get(route('admin.amcs.show', $amc))->assertOk()
            ->assertSee('Mumbai')->assertSee('hello@example.test')->assertSee('Not available')
            ->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="javascript:', false);
        $this->get('/admin/amcs/999999')->assertNotFound();
    }

    public function test_amc_pages_require_an_admin(): void
    {
        $amc = Amc::create(['amfi_id' => 53, 'name' => 'Example Mutual Fund']);
        $this->get(route('admin.amcs.index'))->assertRedirect(route('login'));
        $this->get(route('admin.amcs.show', $amc))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['is_admin' => false]));
        $this->get(route('admin.amcs.index'))->assertRedirect('/');
        $this->get(route('admin.amcs.show', $amc))->assertRedirect('/');
    }

    public function test_empty_state_and_pagination_are_available(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true]));
        $this->get(route('admin.amcs.index'))->assertOk()->assertSee('No AMCs found');
        foreach (range(1, 26) as $number) {
            Amc::create(['amfi_id' => $number, 'name' => sprintf('Fund %02d', $number)]);
        }
        $this->get(route('admin.amcs.index', ['search' => 'Fund', 'page' => 2]))->assertOk()
            ->assertSee('Fund 26')->assertDontSee('Fund 01')
            ->assertViewHas('amcs', fn ($rows) => $rows->total() === 26 && $rows->count() === 1);
    }
}
