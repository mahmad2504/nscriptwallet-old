<?php

namespace App\Console\Commands\Reviews;

use Illuminate\Console\Command;
use App\Apps\Reviews\Reviews;
use App\Email;
class ReviewsSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reviews:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';
	protected $app = null;
	 /**
     * The console command description.
     *
     * @var string
     */
		
    protected $description = 'Sync review notification Jira tickets';

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
		$app = new Reviews($this->option());
		$app->Run();
    }
}
