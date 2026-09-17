<?php

namespace App\Providers;

use App\Services\CronExecutionLogger;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        Event::listen(CommandStarting::class, [CronExecutionLogger::class, 'starting']);
        Event::listen(CommandFinished::class, [CronExecutionLogger::class, 'finished']);
        $this->app->terminating(fn () => $this->app->make(CronExecutionLogger::class)->flushUnfinished());
    }
}
