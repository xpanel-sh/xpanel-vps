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
        $schedule->command('xpanel:instances:retry-ssl')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('xpanel:hosting-lifecycle')->everyFiveMinutes()->withoutOverlapping();
        $schedule->command('xpanel:telemetry-send')->twiceDaily(2, 14)->withoutOverlapping();
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
