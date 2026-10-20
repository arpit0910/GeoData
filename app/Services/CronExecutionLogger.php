<?php

namespace App\Services;

use App\Models\CronLog;
use Carbon\CarbonImmutable;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class CronExecutionLogger
{
    /** @var array<string, array<int, array{title: string, log_id: ?int, started_at: CarbonImmutable, source: string, ip: ?string}>> */
    private static array $started = [];

    public function starting(CommandStarting $event): void
    {
        $title = $this->trackedTitle($event->command);
        if ($title === null) {
            return;
        }

        $startedAt = CarbonImmutable::now('Asia/Kolkata');
        $source = $this->source();
        $ip = $this->ipAddress();

        self::$started[$event->command][] = [
            'title' => $title,
            'log_id' => $this->createStartedLog($title, $startedAt, $source, $ip),
            'started_at' => $startedAt,
            'source' => $source,
            'ip' => $ip,
        ];
    }

    public function finished(CommandFinished $event): void
    {
        $title = $this->trackedTitle($event->command);
        if ($title === null) {
            return;
        }

        $finishedAt = CarbonImmutable::now('Asia/Kolkata');
        $execution = array_pop(self::$started[$event->command]) ?? [
            'title' => $title,
            'log_id' => null,
            'started_at' => $finishedAt,
            'source' => $this->source(),
            'ip' => $this->ipAddress(),
        ];

        $this->finishLog(
            $execution['log_id'],
            $execution['title'],
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
                $this->finishLog(
                    $execution['log_id'],
                    $execution['title'],
                    1,
                    $execution['started_at'],
                    $finishedAt,
                    $execution['source'],
                    $execution['ip']
                );
            }
        }
    }

    public function storeOutput(string $title, string $output): void
    {
        try {
            if (! Schema::hasColumn('cron_logs', 'output')) {
                return;
            }

            $output = trim((string) preg_replace('/\e\[[\d;]*m/', '', $output));
            if ($output === '') {
                return;
            }

            CronLog::query()
                ->where('title', $title)
                ->latest('id')
                ->first()
                ?->update(['output' => Str::limit($output, 20000, "\n...[output truncated]")]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function createStartedLog(
        string $command,
        CarbonImmutable $startedAt,
        string $source,
        ?string $ip
    ): ?int {
        try {
            if (! Schema::hasTable('cron_logs')) {
                return null;
            }

            return CronLog::create([
                'title' => $command,
                'ip' => $ip,
                'source' => $source,
                'status' => false,
                'exit_code' => null,
                'started_at' => $startedAt,
                'finished_at' => null,
                'ran_at' => $startedAt,
            ])->getKey();
        } catch (Throwable $exception) {
            report($exception);

            return null;
        }
    }

    private function finishLog(
        ?int $logId,
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

            $values = [
                'title' => $command,
                'ip' => $ip,
                'source' => $source,
                'status' => $exitCode === 0,
                'exit_code' => $exitCode,
                'started_at' => $startedAt,
                'finished_at' => $finishedAt,
                'ran_at' => $finishedAt,
            ];

            if ($logId && ($log = CronLog::find($logId))) {
                $log->update($values);
            } else {
                CronLog::create($values);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function trackedTitle(?string $command): ?string
    {
        if (! $command) {
            return null;
        }

        $definitions = collect(config('cron_jobs.jobs', []))
            ->where('command', $command);

        $requestedTitle = getenv('SETUGEO_CRON_TITLE');
        if ($requestedTitle && $definitions->contains('title', $requestedTitle)) {
            return $requestedTitle;
        }

        return $definitions->first()['title'] ?? null;
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
