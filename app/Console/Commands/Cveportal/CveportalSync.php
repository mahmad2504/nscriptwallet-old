<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Cveportal;

use App\Email;
use Illuminate\Support\Facades\Artisan;
class CveportalSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0} {{--organization=default}}';

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
		$app =  new Cveportal($this->option());
		$app->Run();
    }
}