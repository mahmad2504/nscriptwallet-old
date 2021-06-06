<?php
namespace App\Apps\Finance;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Finance extends App{
	public $timezone='Asia/Karachi';
	public $scriptname = 'Finance';
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this,'shamojee');
    }
	public function TimeToRun($update_every_xmin=10)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	public function InConsole($yes)
	{
		
	}

	public function Rebuild()
	{
		$this->options['email']=0;// no emails when rebuild
	}
	public function Script()
	{
		dump("Running script");
	}
}