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
    protected function schedule_MENTOR_SERVER(Schedule $schedule)
    {
		dump("Running scedule for mentor services");
		$schedule->exec('del logs\* /q')->daily();
        $schedule->command('ishipment:sync')->everyMinute()
											 ->appendOutputTo("logs/ishipment.txt")->runInBackground();				 
		$schedule->command('lshipment:sync')->everyMinute()
											 ->appendOutputTo("logs/lshipment.txt")->runInBackground();
		$schedule->command('epicupdate:sync')->everyMinute()
											 ->appendOutputTo("logs/epicupdate.txt")->runInBackground();
		$schedule->command('milestone:sync --email=1')->everyMinute()
                                             ->appendOutputTo("logs/milestone.txt")->runInBackground();
		$schedule->command('pullrequest:sync --email=1')->everyMinute()
                                             ->appendOutputTo("logs/pullrequest.txt")->runInBackground();
		$schedule->command('sprintcalendar:sync')->everyMinute()
                                             ->appendOutputTo("logs/sprintcalendar.txt")->runInBackground();										
		$schedule->command('support:sync --email=1')->everyMinute()
                                             ->appendOutputTo("logs/support.txt")->runInBackground();
		$schedule->exec('curl -L "https://script.google.com/macros/s/AKfycbzsxNokdsDLDv6wcNOYDlPX8gGeAYzvHvNB4Ptdftz9hbPUZvkXclEv/exec?func=alive&device=scriptwallet')->everyThirtyMinutes()->appendOutputTo("logs/google.txt");									 
	}
	protected function schedule_PSX_SERVER(Schedule $schedule)
	{
		dump("Running scedule for PSX services");
		$schedule->exec('del logs\* /q')->daily();
		$schedule->command('psx:announcement:sync --email=1')->everyMinute()
											 ->appendOutputTo("logs/psx_announcement.txt")->runInBackground();	
        $schedule->command('psx:financial:sync --email=1')->everyMinute()
											 ->appendOutputTo("logs/psx_financial.txt")->runInBackground();
		$schedule->command('psx:bookclosure:sync --email=1')->everyMinute()
											 ->appendOutputTo("logs/psx_bookclosure.txt")->runInBackground();
										
		$schedule->exec('curl -L "https://script.google.com/macros/s/AKfycbzsxNokdsDLDv6wcNOYDlPX8gGeAYzvHvNB4Ptdftz9hbPUZvkXclEv/exec?func=alive&device=psx"')->everyThirtyMinutes()->appendOutputTo("logs/google.txt");									 
	}
	protected function schedule(Schedule $schedule)
    {
		if(env('APP_NAME') == 'PSX_SERVER')
			$this->schedule_PSX_SERVER($schedule);
		else if(env('APP_NAME') == 'MENTOR_SERVER')
			$this->schedule_MENTOR_SERVER($schedule);
		else
			dd('Schedule for '.APP_NAME.' is not defined');
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
