<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Cve;

use App\Email;
use Illuminate\Support\Facades\Artisan;
class CveportalSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

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
	
    public function handle()//
    {
		$cve =  new Cve();
		$url = $cve->GetGoogleUrl();
		$url = $url."?request=commands";
		$data = json_decode(file_get_contents($url));
		if($data->retval != 'ok')
			return;
		
		$commands =  explode(',',$data->data);
		foreach($commands as $command)
		{
			switch($command)
			{
				case 'productsync':
					dump('Product Sync');
					Artisan::call("cveportal:product:sync --google=1 --force=1");
					break;
				case 'nvdsync':
					dump('NVD Sync');
					Artisan::call("cveportal:nvd:sync --force=1");
					break;
				case 'svmsync':
					dump('SVM Sync');
					Artisan::call("cveportal:svm:sync --force=1");
					break;
				case 'cvesync':
					dump('CVE Sync');
					Artisan::call("cveportal:cve:sync --force=1");
					break;
				case 'staticpagessync':
					dump('Publish CVE Externally');
					Artisan::call("cveportal:staticpages:sync --force=1");
					break;
				default:
					dump($command.' Command cannot be executed ');
					break;
				
			}
		}
		$url = $cve->GetGoogleUrl();
		$url = $url."?request=status_cveportalupdated";
		$data = json_decode(file_get_contents($url));
		dump('Done');
	
    }
}