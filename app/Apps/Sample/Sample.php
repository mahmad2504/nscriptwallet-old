<?php
namespace App\Apps\Sample;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;

class Sample extends App{
	public $timezone='Asia/Karachi';
	public $query='labels in (risk,milestone) and duedate >=';
	public $jira_fields = ['key','status','statuscategory','summary']; 
    //public $jira_customfields = ['sprint'=>'Sprint'];  	
	public $jira_server = 'EPS';
	
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);

    }
	public function TimeToRun($update_every_xmin=10)
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
	public function Script()
	{
		dump("Running script");
		//$tickets =  $this->FetchJiraTickets();
	}
}