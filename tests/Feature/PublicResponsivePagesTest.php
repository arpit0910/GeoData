<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicResponsivePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_and_docs_pages_include_mobile_overflow_guards(): void
    {
        $this->get(route('about'))
            ->assertOk()
            ->assertSee('overflow-x-hidden', false)
            ->assertSee('sm:hover:rotate-0', false);

        $this->get(route('docs'))
            ->assertOk()
            ->assertSee('id="api-docs"', false)
            ->assertSee('id="api-docs-content"', false)
            ->assertSee('#api-docs-content table', false);
    }

    public function test_market_uses_compact_mobile_pagination(): void
    {
        $this->get(route('market.stocks'))
            ->assertOk()
            ->assertSee('.market-pagination nav>div:first-child{display:flex', false)
            ->assertSee('@media(min-width:640px)', false)
            ->assertSee('overflow-x:hidden', false);
    }
}
