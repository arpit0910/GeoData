<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ServerLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_tail_of_a_large_log_file(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $filename = 'log-viewer-test.log';
        $path = storage_path('logs/'.$filename);
        $lines = [];

        foreach (range(1, 1305) as $number) {
            $marker = $number === 1 ? ' oldest-marker' : ($number === 1305 ? ' newest-marker' : '');
            $lines[] = "[2026-09-28 18:00:00] testing.INFO: log line {$number}{$marker}";
        }

        File::ensureDirectoryExists(dirname($path));
        File::put($path, implode(PHP_EOL, $lines));

        try {
            $this->actingAs($admin)
                ->get(route('admin.logs', ['file' => $filename]))
                ->assertOk()
                ->assertSee('1,200')
                ->assertSee('1,305')
                ->assertSee('newest-marker')
                ->assertDontSee('oldest-marker');
        } finally {
            File::delete($path);
        }
    }

    public function test_non_admin_cannot_view_server_logs(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.logs'))
            ->assertRedirect('/');
    }
}
