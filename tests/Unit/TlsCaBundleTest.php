<?php

namespace Tests\Unit;

use App\Support\TlsCaBundle;
use Tests\TestCase;

class TlsCaBundleTest extends TestCase
{
    /** @test */
    public function it_uses_the_system_trust_store_when_no_bundle_is_configured(): void
    {
        $this->assertTrue(TlsCaBundle::resolve(null));
        $this->assertTrue(TlsCaBundle::resolve(''));
    }

    /** @test */
    public function it_resolves_a_server_root_vendor_path_from_the_application_root(): void
    {
        $expected = realpath(base_path('vendor/autoload.php'));

        $this->assertNotFalse($expected);
        $this->assertSame($expected, TlsCaBundle::resolve('/vendor/autoload.php'));
    }

    /** @test */
    public function it_falls_back_to_the_system_trust_store_for_a_missing_bundle(): void
    {
        $this->assertTrue(TlsCaBundle::resolve('/vendor/missing/cacert.pem'));
    }
}
