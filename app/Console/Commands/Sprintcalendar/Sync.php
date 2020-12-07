<?php

namespace App\Console\Commands\Sprintcalendar;

use Illuminate\Console\Command;
use App\Apps\Sprintcalendar\Sprintcalendar;
use App\Email;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sprintcalendar:sync {--rebuild=0} {--force=0} {--email=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Sprint start and end Notifications ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
	
    public function handle()
    {
		$app = new SprintCalendar(null,null,$this->option());
		$app->Run();
    }
}
