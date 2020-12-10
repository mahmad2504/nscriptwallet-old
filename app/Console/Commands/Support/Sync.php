<?php

namespace App\Console\Commands\Support;

use Illuminate\Console\Command;
use App\Apps\Support\Support;
use App\Email;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';
	protected $app = null;
	 /**
     * The console command description.
     *
     * @var string
     */
		
    protected $description = 'Sync support Jira tickets';

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
		$app = new Support($this->option());
		$app->Run();
    }
}
