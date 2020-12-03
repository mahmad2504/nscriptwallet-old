<?php
namespace App\Apps\Pullrequest;
use App\Apps\App;

class Pullrequest extends App{
	public $timezone='Asia/Karachi';
	public $email_from='Waqar_Humayun@mentor.com';
	public $email_cc='Waqar_Humayun@mentor.com';
	public $email_escalate='Rizwan_Rasheed@mentor.com';
	
	public $urls = [
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tf/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-docs/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-source/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-packaging/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tests/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/QA/repos/bspvk/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/NUMET/repos/scaptic-automation/pull-requests',
	//'http://stash.alm.mentorg.com/rest/api/1.0/projects/NUMET/repos/scaptic-framework/pull-requests',
	];
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
			case 'sprint':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
				}
				return '';
				break;	
		}
	}
}