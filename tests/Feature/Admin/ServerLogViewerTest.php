<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ServerLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_the_newest_entries_of_a_large_log_file(): void
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
                ->assertSee('100')
                ->assertSee('1,305')
                ->assertSee('newest-marker')
                ->assertDontSee('oldest-marker');
        } finally {
            File::delete($path);
        }
    }

    public function test_search_keeps_an_entire_error_together_and_escapes_its_content(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $filename = 'log-viewer-entries-test.log';
        $path = storage_path('logs/'.$filename);
        File::put($path, "[2026-10-29 00:00:00] production.ERROR: First failure <script>alert(1)</script>\n[stacktrace]\n#0 unique-trace.php\n[2026-10-29 00:01:00] production.INFO: Other event\n");
        try {
            $response = $this->actingAs($admin)->get(route('admin.logs', [
                'file' => $filename, 'level' => 'error', 'q' => 'unique-trace',
            ]));
            $response->assertOk()->assertSee('First failure')->assertSee('unique-trace.php')
                ->assertDontSee('Other event')->assertDontSee('<script>alert(1)</script>', false);
            $this->assertCount(1, $response->viewData('entries'));
            $this->assertSame(2, $response->viewData('totalEntries'));
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
