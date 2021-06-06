<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Jiraa;
class JiraSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:jira:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync CVE with Jira';

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
		
		$app = new Jiraa($this->option());
		$app->Run();
    }
}