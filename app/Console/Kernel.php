<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
namespace App\Console\Commands;  


class Kernel extends ConsoleKernel
{
    // Register your commands here
    protected $commands = [
        \App\Console\Commands\SendScheduledRideNotification::class,
        \App\Console\Commands\GenerateDailySchedule::class,
        // Add other commands here if needed
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('send:daily_schedule')->everyMinute();
    }
    
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
