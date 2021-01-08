<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Nvd;
use App\Apps\Cveportal\Svm;
use App\Apps\Cveportal\Cve;
use App\Apps\Cveportal\Product;
use Artisan;
class Cveportal extends App{
	public $timezone='Asia/Karachi';
	public $scriptname = 'cveportal';
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);

    }
	public function TimeToRun($update_every_xmin=60)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
	}
	public function Rebuild()
	{
		//$this->db->cards->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	public function Script()
	{
		$options = $this->Options();
		Artisan::call('product:sync',$options );
		Artisan::call('svm:sync', $options);
		Artisan::call('nvd:sync', $options);
		Artisan::call('cve:sync', $options);
	}
}