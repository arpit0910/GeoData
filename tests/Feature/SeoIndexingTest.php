<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoIndexingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function sitemap_lists_canonical_public_pages_and_excludes_removed_pricing_page()
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee(route('home'))
            ->assertSee(route('docs'))
            ->assertSee(route('market.index'))
            ->assertSee(route('market.stocks'))
            ->assertDontSee('/pricing');
    }

    /** @test */
    public function homepage_has_stable_canonical_brand_metadata_and_schema()
    {
        config(['app.url' => 'https://setugeo.com']);

        $response = $this->withServerVariables(['HTTP_HOST' => 'www.setugeo.com'])->get('/');

        $response->assertOk()
            ->assertSee('<link rel="canonical" href="https://setugeo.com">', false)
            ->assertSee('name="application-name" content="SetuGeo"', false)
            ->assertSee('SetuGeo Geographic Data API')
            ->assertSee('"@type": "WebSite"', false)
            ->assertDontSee('aggregateRating');
    }

    /** @test */
    public function private_and_duplicate_pages_are_not_indexable()
    {
        $this->get('/login')->assertOk()
            ->assertSee('name="robots" content="noindex, nofollow, noarchive"', false);

        $this->get('/landing-v1')->assertOk()
            ->assertSee('name="robots" content="noindex, follow"', false);

        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Disallow: /api/', $robots);
        $this->assertStringContainsString('Sitemap: https://setugeo.com/sitemap.xml', $robots);
    }

    /** @test */
    public function web_server_config_redirects_www_to_the_canonical_hostname()
    {
        $rules = file_get_contents(public_path('.htaccess'));

        $this->assertStringContainsString('^www\\.setugeo\\.com$', $rules);
        $this->assertStringContainsString('https://setugeo.com%{REQUEST_URI}', $rules);
    }
}
