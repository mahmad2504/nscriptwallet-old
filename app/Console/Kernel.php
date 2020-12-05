<?php

namespace App\Console;

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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
		$schedule->exec('del logs\* /q')->daily();
        $schedule->command('ishipment:sync')->everyMinute()
											 ->appendOutputTo("logs/ishipment.txt")->runInBackground();
											 
		$schedule->command('lshipment:sync')->everyMinute()
											 ->appendOutputTo("logs/lshipment.txt")->runInBackground();
		$schedule->command('epicupdate:sync')->everyMinute()
											 ->appendOutputTo("logs/epicupdate.txt")->runInBackground();	
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
