<?php

namespace App\Services;

use App\Models\CronLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Schema;
use Throwable;

class CronExecutionLogger
{
    /** @var array<string, array<int, array{started_at: CarbonImmutable, source: string, ip: ?string}>> */
    private static array $started = [];

    public function starting(CommandStarting $event): void
    {
        if (! $this->isTracked($event->command)) {
            return;
        }

        self::$started[$event->command][] = [
            'started_at' => CarbonImmutable::now('Asia/Kolkata'),
            'source' => $this->source(),
            'ip' => $this->ipAddress(),
        ];
    }

    public function finished(CommandFinished $event): void
    {
        if (! $this->isTracked($event->command)) {
            return;
        }

        $finishedAt = CarbonImmutable::now('Asia/Kolkata');
        $execution = array_pop(self::$started[$event->command]) ?? [
            'started_at' => $finishedAt,
            'source' => $this->source(),
            'ip' => $this->ipAddress(),
        ];

        $this->write(
            $event->command,
            (int) $event->exitCode,
            $execution['started_at'],
            $finishedAt,
            $execution['source'],
            $execution['ip']
        );
    }

    public function flushUnfinished(): void
    {
        $finishedAt = CarbonImmutable::now('Asia/Kolkata');

        foreach (self::$started as $command => $executions) {
            while ($execution = array_pop(self::$started[$command])) {
                $this->write(
                    $command,
                    1,
                    $execution['started_at'],
                    $finishedAt,
                    $execution['source'],
                    $execution['ip']
                );
            }
        }
    }

    private function write(
        string $command,
        int $exitCode,
        CarbonImmutable $startedAt,
        CarbonImmutable $finishedAt,
        string $source,
        ?string $ip
    ): void {

        try {
            if (! Schema::hasTable('cron_logs')) {
                return;
            }

            CronLog::create([
                'title' => $command,
                'ip' => $ip,
                'source' => $source,
                'status' => $exitCode === 0,
                'exit_code' => $exitCode,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'ran_at' => $finishedAt,
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function isTracked(?string $command): bool
    {
        if (! $command) {
            return false;
        }

        return collect(config('cron_jobs.jobs', []))->contains('command', $command);
    }

    private function source(): string
    {
        if (getenv('SETUGEO_CRON_SOURCE') === 'scheduled') {
            return 'scheduled';
        }

        if (app()->bound('request') && request()->route()) {
            return 'manual';
        }

        return 'cli';
    }

    private function ipAddress(): ?string
    {
        if (app()->bound('request') && request()->route()) {
            return request()->ip();
        }

        $host = gethostname();
        return $host ? gethostbyname($host) : null;
    }
}
