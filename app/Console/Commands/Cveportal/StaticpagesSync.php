<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Staticpages;
use App\Email;
class StaticpagesSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:staticpages:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0} {--organization=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Description';

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
		$app = new Staticpages($this->option());
		$app->Run();
    }
}