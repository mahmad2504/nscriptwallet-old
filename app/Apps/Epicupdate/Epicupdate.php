<?php
namespace App\Apps\Epicupdate;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;

class Epicupdate extends App{
	public $timezone='Asia/Karachi';
	public $query="key in (VSTARMOD-26574) or ".
	"issue in linkedIssues(ANDPR-266, 'releases') and type=Epic  and component in (CVBL) and status !=Released or ".
	"issue in linkedIssues(ANDPR-286, 'releases') and type=Epic  and component in (CVBL) and status !=Released";
	
	public $jira_fields = ['key','status','statuscategory','summary','description','issuelinks',
		'timespent','resolution','timeremainingestimate','timeoriginalestimate','timetracking',
		'resolutiondate','updated','duedate','subtasks','issuetype','subtask',
		'labels','fixVersions'];
		
	public $jira_customfields = ['epic_link'=>'Epic Link','story_points'=>'Story Points','sprint'=>'Sprint'];
	public $jira_server = 'IESD';
	public $options = null;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		//$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
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
			case 'story_points':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
				}
				return 0;
				break;
			case 'sprint':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
				}
				return '';
			case 'epic_link':
				if(isset($issue->fields->customFields[$code]))
					return $issue->fields->customFields[$code];
				else
					return '';
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
	}
	public function Script()
	{
		dump("Running script");
		$tickets =  $this->FetchJiraTickets();
		foreach($tickets as $ticket)
		{
			$query="'Epic Link'=".$ticket->key;
			dump("Updating Epic ".$ticket->key);
			$stasks = $this->FetchJiraTickets($query);
			$timeoriginalestimate= 0;
			$timeremainingestimate = 0;
			$timespent = 0;
			foreach($stasks as $task)
			{
				if(count($task->subtasks)>0)
				{
					$query="issueFunction in subtasksOf ('key=".$task->key."')";
					dump($query);
					$ctasks = $this->FetchJiraTickets($query);
					foreach($ctasks as $ctask)
					{
						$timeoriginalestimate += $ctask->timeoriginalestimate;
						$timeremainingestimate += $ctask->timeremainingestimate;
						$timespent += $ctask->timespent;
					}
				}
				$timeoriginalestimate += $task->timeoriginalestimate;
				$timeremainingestimate += $task->timeremainingestimate;
				$timespent += $task->timespent;
			}
			Jira::UpdateTimeTrack($ticket->key,$timeoriginalestimate,$timeremainingestimate,$timespent);
		}
	}
}