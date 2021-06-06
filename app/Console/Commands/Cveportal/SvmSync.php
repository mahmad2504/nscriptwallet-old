<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Svm;
use App\Email;
class SvmSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:svm:sync {--rebuild=0} {--force=0} {--email=2} {--organization=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update SVM Data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
	
    public function handle()//
    {
		
		$app = new Svm($this->option());
		$app->Run();
    }
}