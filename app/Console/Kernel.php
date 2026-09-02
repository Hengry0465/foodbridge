<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * Console Kernel - register scheduled commands
 */
class Kernel extends ConsoleKernel
{
    protected $commands = [
        Commands\ExpirePickupsCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run pickup expiry command every minute for demonstration
        $schedule->command('pickups:expire')->everyMinute();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
