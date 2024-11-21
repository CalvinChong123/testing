<?php

namespace App\Console;

use App\Jobs\ExportHubExpireCheck;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Define the application's command schedule.
     *
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        $telescopePruneHours = 31 * 24;
        $schedule->command('telescope:prune --hours='.$telescopePruneHours)->dailyAt('02:00'); // daily at 2am

        $schedule->job(new ExportHubExpireCheck)->dailyAt('02:00'); // daily at 2am
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
