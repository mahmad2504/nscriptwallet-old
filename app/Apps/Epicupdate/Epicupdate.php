<?php
namespace App\Apps\Epicupdate;
use App\Apps\App;

class Epicupdate extends App{
	public $timezone='Asia/Karachi';
	public $query="key in (VSTARMOD-26574) or ".
	"issue in linkedIssues(ANDPR-266, 'releases') and type=Epic  and component in (CVBL) and status !=Released or ".
	"issue in linkedIssues(ANDPR-286, 'releases') and type=Epic  and component in (CVBL) and status !=Released";
		
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