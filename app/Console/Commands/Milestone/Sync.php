<?php

namespace App\Console\Commands\Milestone;

use Illuminate\Console\Command;
use App\Apps\Milestone\Milestone;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'milestone:sync {--rebuild=0} {--force=0} {--email=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Milestone Notifications based on due dates';

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
		$this->app = new Milestone($this->option());
		$this->app->Run();
    }
}
