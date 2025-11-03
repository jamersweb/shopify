<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Schedule tracking sync every 2 hours
        $schedule->command('ecofreight:schedule-tracking-sync')
                 ->everyTwoHours()
                 ->withoutOverlapping()
                 ->runInBackground();

        // Clean up stale shipments daily at 2 AM
        $schedule->command('ecofreight:cleanup-stale')
                 ->dailyAt('02:00')
                 ->withoutOverlapping();
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
