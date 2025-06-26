<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\CreateMessageSystem::class,
        Commands\ClearMessageCache::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // PBC SCHEDULED COMMANDS
        $schedule->command('pbc:check-overdue')
                 ->dailyAt('09:00')
                 ->appendOutputTo(storage_path('logs/pbc-overdue.log'));

        $schedule->command('pbc:send-reminders')
                 ->dailyAt('08:00')
                 ->appendOutputTo(storage_path('logs/pbc-reminders.log'));

        $schedule->command('pbc:cleanup-logs')
                 ->monthly()
                 ->appendOutputTo(storage_path('logs/pbc-cleanup.log'));

        $schedule->command('pbc:clear-message-cache')->daily();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
