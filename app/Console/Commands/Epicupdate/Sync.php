<?php

namespace App\Console\Commands\Sample;

use Illuminate\Console\Command;
use App\Apps\Epicupdate\Epicupdate;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
   protected $signature = 'epicupdate:sync {--rebuild=0} {--force=0} {--beat=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updated estimates and time on epic';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
    public function Permission()
	{
		$this->app = new Epicupdate();
		$this->rebuild = $this->option('rebuild');
		$sync_requested = $this->app->Read('sync_requested');
		$this->force = $this->option('force');
		if(($this->rebuild == 1)||($this->force)||$sync_requested)
			return true;
		return $this->app->Permission(60);	// every hour 	
	}
	public function ConfigureJiraFields($fields)
	{
		dump("Configuring Jira fields");
		$fields->Set(['key','status','statuscategory','summary',
		'description','issuelinks',  //transitions
		'timespent','resolution','timeremainingestimate','timeoriginalestimate','timetracking',
		'resolutiondate','updated','duedate','subtasks','issuetype','subtask',
		'labels','fixVersions','issuetypecategory']);
		$fields->Set(['epic_link'=>'Epic Link','story_points'=>'Story Points','sprint'=>'Sprint']);
		$fields->Dump();
	}
	public function Preprocess()
	{
		
	}
	public function Postprocess()
	{
		$this->app->SaveUpdateTime();
		$this->app->Save(['sync_requested'=>0]);
	}
    public function handle()
    {
		if(!$this->Permission())
		{
			echo "Not permitted at this time\n";
			return;
		}
		$app = $this->app;
		$this->Preprocess();
		Jira::Init('IESD',$app);
		$fields = new Fields($this->app);
		if(!$fields->Exists()||$this->rebuild)
			$this->ConfigureJiraFields($fields);
		$tickets =  Jira::FetchTickets($app->query,$fields);
		foreach($tickets as $ticket)
		{
			$query="'Epic Link'=".$ticket->key;
			dump("Updating Epic ".$ticket->key);
			$stasks = Jira::FetchTickets($query,$fields);
			$timeoriginalestimate= 0;
			$timeremainingestimate = 0;
			$timespent = 0;
			foreach($stasks as $task)
			{
				if(count($task->subtasks)>0)
				{
					$query="issueFunction in subtasksOf ('key=".$task->key."')";
					dump($query);
					$ctasks = Jira::FetchTickets($query,$fields);
					
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
		$this->Postprocess();
    }
}
