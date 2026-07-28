<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ApiTestRunnerService;
use Illuminate\Console\Command;

class RunCompanyApiHealthCheckCommand extends Command
{
    protected $signature = 'api:test-company
        {user : User ID or email of the target company account}
        {--mode=production : demo or production}
        {--endpoint=* : Optional endpoint keys to run instead of the full supported catalog}';

    protected $description = 'Run the API health suite for a specific company and persist the report.';

    public function handle(ApiTestRunnerService $runner): int
    {
        $identifier = (string) $this->argument('user');
        $mode = $this->option('mode') === 'demo' ? 'demo' : 'production';

        $targetUser = User::query()
            ->where('email', $identifier)
            ->orWhere('id', $identifier)
            ->first();

        if (! $targetUser) {
            $this->error('No matching user found for: ' . $identifier);

            return self::FAILURE;
        }

        $actor = $targetUser;
        if ($mode === 'demo' && ! $targetUser->is_admin) {
            $actor = User::query()->where('is_admin', true)->orderBy('id')->first();

            if (! $actor) {
                $this->error('Demo mode requires at least one admin user to act as the internal tester.');

                return self::FAILURE;
            }
        }

        $report = $runner->runAndStore($actor, $targetUser, (array) $this->option('endpoint'), $mode);

        $this->info($report->report_name);
        $this->line('Mode: ' . $report->mode);
        $this->line('Passed: ' . $report->passed_endpoints . '/' . $report->total_endpoints);
        $this->line('Failed: ' . $report->failed_endpoints);
        $this->line('Skipped: ' . ($report->skipped_endpoints ?? 0));
        $this->line('Avg Duration: ' . $report->average_duration_ms . ' ms');

        return $report->failed_endpoints > 0 ? self::FAILURE : self::SUCCESS;
    }
}
