<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Product;
use App\Apps\Cveportal\Instance;
use Artisan;
class Cveportal extends App{
	public $scriptname = 'cveportal';
	public $timezone='Asia/Karachi';
	public $producturl = null;
	public $default_triage_status = 'Investigate';
	public $svmurl='https://svm.cert.siemens.com/portal/api/v1';
	public $svmproxyserver='http://cyp-fsrprx.net.plm.eds.com:2020';
	public $organizations = ['mentorgraphics','mentorgraphics_test'];
	public function __construct($options)
    {
		$this->options = $options;
		$inst = Instance::Get($options['organization']);
		$this->producturl = $inst->producturl;
		$this->options['dbname'] = $inst->dbname;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		parent::__construct($this);
    }
	
	public function TimeToRun($update_every_xmin=1)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		$this->options['email']=0;// no emails when rebuild
	}
	public function Script()
	{
		foreach($this->organizations as $organization)
		{
			dump('Running for organization '.$organization);
			$inst = Instance::Get($organization);
			if($inst == null)
				dd($organization." organization not found");
			$url = $inst->producturl."?request=commands";
			$data = json_decode(file_get_contents($url));
			if($data->retval != 'ok')
				dd($organization." google data not received");
			$commands =  explode(',',$data->data);
			foreach($commands as $command)
			{
				if(trim($command)=='')
					continue;
				switch($command)
				{
					case 'productsync':
						dump('Product Sync');
						Artisan::call("cveportal:product:sync --force=1 --organization=".$organization);
						break;
					case 'nvdsync':
						dump('NVD Sync');
						Artisan::call("cveportal:nvd:sync --force=1 --organization=".$organization);
						break;
					case 'svmsync':
						dump('SVM Sync');
						Artisan::call("cveportal:svm:sync --force=1 --organization=".$organization);
						break;
					case 'cvesync':
						dump('CVE Sync');
						Artisan::call("cveportal:cve:sync --force=1 --organization=".$organization);
						break;
					case 'staticpagessync':
						dump('Publish CVE Externally');
						Artisan::call("cveportal:staticpages:sync --force=1 --organization=".$organization);
						break;
					default:
						dump($command.' Command cannot be executed ');
						break;
				}
			}
			$url = $inst->producturl."?request=status_cveportalupdated";
			$data = json_decode(file_get_contents($url));
			Artisan::call("cveportal:svm:sync  --organization=".$organization);
			Artisan::call("cveportal:nvd:sync  --organization=".$organization);
			Artisan::call("cveportal:cve:sync  --organization=".$organization);
			Artisan::call("cveportal:staticpages:sync  --organization=".$organization);
		}
	}
}