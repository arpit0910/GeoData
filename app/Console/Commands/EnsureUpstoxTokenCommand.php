<?php

namespace App\Console\Commands;

use App\Services\UpstoxTokenManager;
use Illuminate\Console\Command;
use Throwable;

class EnsureUpstoxTokenCommand extends Command
{
    protected $signature = 'market:ensure-upstox-token {--force : Request a token even when one is active or pending}';
    protected $description = 'Ensure an Upstox token is active or request an approved replacement';

    public function handle(UpstoxTokenManager $tokens): int
    {
        $current = $tokens->current();
        if ($current && ! $this->option('force')) {
            $this->info('The stored Upstox token is active until '.$current->expires_at->toIso8601String().'.');
            return self::SUCCESS;
        }

        try {
            $result = $tokens->requestRenewal((bool) $this->option('force'));
            $verb = $result['requested'] ? 'requested' : 'already pending';
            $this->info('Upstox token renewal '.$verb.'. Approve it in Upstox; the notifier webhook will store it automatically.');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            report($exception);
            return self::FAILURE;
        }
    }
}
