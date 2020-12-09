<?php

namespace App\Console\Commands\Support;

use Illuminate\Console\Command;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use App\Apps\Support\Support;
use App\Email;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'support:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';
	protected $app = null;
	 /**
     * The console command description.
     *
     * @var string
     */
		
    protected $description = 'Sync support Jira tickets';

    /**
     * Create a new command instance.
     *
     * @return void
     */

    public function __construct()
    {
		parent::__construct();
    }
	public function ConfigureJiraFields($fields)
	{
		dump("Configuring Jira fields");
		$fields->Set(['issuelinks','resolution','issuetype','assignee','priority','key','summary','status','statuscategory','resolutiondate','created','updated','transitions']);
		$fields->Set(['premium_support'=>'Premium Support',
				'first_contact_date'=>'First Contact Date',
				'violation_time_to_resolution'=>'Violation Time to Resolution',
				'gross_time_to_resolution'=>'Gross Time to Resolution',   
				'gross_minutes_to_resolution'=>'gross_minutes_to_resolution',  
				'net_time_to_resolution'=>'Net Time to Resolution',
				//'waiting_time'=>'Time in Status(WFC)',
				'violation_firstcontact'=>'Violation First Contact',
				'solution_provided_date'=>'Solution Provided Date',
				'test_case_provided_date'=>'Test / Use Case Provided',
				'product_name'=>'Product Name',
				'component'=>'Component',
				'account'=>'Account']);
		$fields->Dump();
	}
	public function Permission()
	{
		$this->app = new Support();
		$rebuild = $this->option('rebuild');
		if($rebuild == 1)
			return true;
		return $this->app->Permission();
	}
	public function Preprocess()
	{
		//$this->app->ProcessDirtyTickets();
		date_default_timezone_set($this->app->timezone);
	}
	public function PostProcess()
	{
		$this->app->SaveUpdateTime();
	}
	
    public function handle()
    {
		if(!$this->Permission())
		{
			echo "Not permitted at this time\n";
			return;
		}
		$this->Preprocess();
		
		Jira::Init('EPS');
		$fields = new Fields($this->app,0);
		$fields->Set(['key','updated']);
		//$this->query = 'key in (SIEJIR-5794)'; 
		dump($this->app->query);
		$tickets =  Jira::FetchTickets($this->app->query,$fields);
		$fields = new Fields($this->app);
		if(!$fields->Exists())
			$this->ConfigureJiraFields($fields);
		$this->Preprocess();
		$del = '';
		$nquery = '';
		foreach($tickets as $ticket)
		{
			$dticket = $this->app->ReadTicket($ticket->key);
			if($dticket == null)
			{
				$nquery .= $del.$ticket->key;
				$del = ',';
			}
			else if($dticket->updated != $ticket->updated)
			{
				$nquery .= $del.$ticket->key;
				$del = ',';
			}
		}
		if($nquery != '')
		{
			$nquery = 'key in ('.$nquery.')';
			$tickets =  Jira::FetchTickets($nquery,$fields);
			dump(count($tickets)." tickets found");
			foreach($tickets as $ticket)
			{
				$ticket->dirty = 1;
				$this->app->SaveTicket($ticket);
			}
		}
		$this->app->ProcessDirtyTickets();
		$this->PostProcess();
    }
}
