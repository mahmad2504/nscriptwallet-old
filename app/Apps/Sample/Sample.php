<?php
namespace App\Apps\Sample;
use App\Apps\App;

class Sample extends App{
	public $timezone='Asia/Karachi';
	public function __construct()
    {
		$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		date_default_timezone_set($this->timezone);
		parent::__construct(__NAMESPACE__, $server);
    }
	public function Permission($update_every_xmin=0)
	{
		return parent::Permission($update_every_xmin);
	}
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			case 'default':
				dd("Implement IssueParser");
		}
	}
}