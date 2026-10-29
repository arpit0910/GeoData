<?php

namespace Tests\Feature;

use App\Console\Commands\SyncDailyMarketMastersCommand;
use Illuminate\Console\Scheduling\Schedule;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

class DailyMarketMastersTest extends TestCase
{
    public function test_midnight_workflow_continues_after_a_failed_step_and_reports_failure(): void
    {
        $command = new class extends SyncDailyMarketMastersCommand {
            public array $steps = [];

            public function call($command, array $arguments = [])
            {
                $this->steps[] = $command;
                return $command === 'mf:sync-and-calculate' ? 1 : 0;
            }
        };
        $command->setLaravel($this->app);
        $exit = $command->run(new ArrayInput([]), new BufferedOutput());

        $this->assertSame(1, $exit);
        $this->assertSame([
            'equities:sync-upstox-instruments',
            'equities:sync-metadata',
            'mf:sync-and-calculate',
            'mf:sync-upstox-daily',
            'market:sync-global-instruments',
        ], $command->steps);
    }

    public function test_master_sync_runs_once_at_midnight_india_time_with_overlap_protection(): void
    {
        $schedule = new Schedule();
        $method = new \ReflectionMethod(\App\Console\Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(app(\Illuminate\Contracts\Console\Kernel::class), $schedule);
        $events = collect($schedule->events())->filter(fn ($event) => str_contains($event->command, 'market:sync-daily-masters'));

        $this->assertCount(1, $events);
        $event = $events->first();
        $this->assertSame('0 0 * * *', $event->expression);
        $this->assertSame('Asia/Kolkata', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
        $this->assertTrue($event->runInBackground);
    }
}
