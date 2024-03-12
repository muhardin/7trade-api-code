<?php

namespace App\Console;

use App\Models\UserMining;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:check-streak-trade')->dailyAt('22:00');
        $schedule->command('app:check-mega-price')->dailyAt('22:00');
        $schedule->command('app:calculate-trading-volume')->dailyAt('22:00');
        $schedule->command('app:active-mining-list')->dailyAt('23:00');
        $schedule->command('app:check-user-mining')->dailyAt('23:00');
        // $schedule->command('app:check-deposit')->everySecond();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
