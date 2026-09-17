<?php

namespace Tests\Feature\Admin;

use App\Models\CronLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CronLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_cli_execution_is_logged_once_with_timing_and_exit_code(): void
    {
        Artisan::command('test:tracked-cron', fn () => 0);
        $this->setJobs(['test:tracked-cron']);

        $this->artisan('test:tracked-cron')->assertExitCode(0);

        $this->assertDatabaseCount('cron_logs', 1);
        $this->assertDatabaseHas('cron_logs', [
            'title' => 'test:tracked-cron',
            'source' => 'cli',
            'status' => true,
            'exit_code' => 0,
        ]);
        $log = CronLog::firstOrFail();
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->finished_at);
        $this->assertNotNull($log->ran_at);
    }

    public function test_admin_run_is_logged_once_as_manual_even_when_command_fails(): void
    {
        Artisan::command('test:failing-cron', fn () => 1);
        $this->setJobs(['test:failing-cron']);
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->postJson(route('admin.crons.run'), [
            'title' => 'test:failing-cron',
        ])->assertStatus(500);

        $this->assertDatabaseCount('cron_logs', 1);
        $this->assertDatabaseHas('cron_logs', [
            'title' => 'test:failing-cron',
            'source' => 'manual',
            'status' => false,
            'exit_code' => 1,
        ]);
    }

    public function test_scheduler_marker_records_scheduled_source(): void
    {
        Artisan::command('test:scheduled-cron', fn () => 0);
        $this->setJobs(['test:scheduled-cron']);
        putenv('SETUGEO_CRON_SOURCE=scheduled');

        try {
            $this->artisan('test:scheduled-cron')->assertExitCode(0);
        } finally {
            putenv('SETUGEO_CRON_SOURCE');
        }

        $this->assertDatabaseHas('cron_logs', [
            'title' => 'test:scheduled-cron',
            'source' => 'scheduled',
            'status' => true,
        ]);
    }

    public function test_cron_dashboard_includes_exchange_calendar_and_uses_latest_log(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        CronLog::create([
            'title' => 'exchange-calendar:sync',
            'source' => 'scheduled',
            'ip' => '127.0.0.1',
            'status' => true,
            'exit_code' => 0,
            'started_at' => now()->subSeconds(2),
            'finished_at' => now(),
            'ran_at' => now(),
        ]);

        $this->actingAs($admin)->get(route('admin.crons.index'))
            ->assertOk()
            ->assertSee('exchange-calendar:sync')
            ->assertSee('Healthy')
            ->assertSee('scheduled');
    }

    private function setJobs(array $commands): void
    {
        config(['cron_jobs.jobs' => collect($commands)->map(fn ($command) => [
            'title' => $command,
            'command' => $command,
            'args' => [],
            'description' => 'Test command',
            'schedule' => 'Manual',
            'timezone' => 'Asia/Kolkata',
            'overlap' => false,
        ])->all()]);
    }
}
