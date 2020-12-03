<?php

namespace App\Console\Commands\waqar;

use mahmad\Jira\Fields;
use mahmad\Jira\Jira;
use Illuminate\Console\Command;
use App\Console\Commands\waqar\Email;

class NucCheckPr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'waqar:nuccheckpr {--fields=null} {{--rebuild=0}} {--beat=0}';
	private $server = 'EPS';
	protected $urls = [
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-source/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-docs/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-packaging/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-source/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tests/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tf/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/QA/repos/bspvk/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/NUMET/repos/scaptic-automation/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/NUMET/repos/scaptic-framework/pull-requests',
	];
	public $datapath = "data/waqar/nuccheckpr/";
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
	public function ConfigureJiraFields()
	{
		dump("Configuring Jira fields");
		
		$fields = new Fields($this->key);
		$fields->Set(['key','status','statuscategory','summary','resolution','resolutiondate','updated','issuetype','fixVersions','issuetypecategory']);
		$fields->Set(['sprint'=>'Sprint']);
		$fields->Dump();
	}
	private  function sortfunc($a,$b) 
	{
	   if(($a->activesprintname != null)&&($b->activesprintname != null))
	      return 0;
	   if($a->activesprintname != null)
		return -1;
	   if($b->activesprintname != null)
		return 1;
	}
	private function GetJiraKey($jira_ref)
	{
		
		$jira_ref = str_replace("[",'',$jira_ref);
		$jira_ref = str_replace("]",'',$jira_ref);
		$jira_ref_parts = explode("-",$jira_ref);
		if(count($jira_ref_parts)==2)
		{
			if((is_numeric($jira_ref_parts[1]))&&(strlen($jira_ref_parts[0])<=4))
			   return $jira_ref;
		}
		return null;
	}
    public function handle()
    {
		$classname = __CLASS__;
		$namespace = __NAMESPACE__;
		$this->key = (substr($namespace, strrpos($namespace, '\\') + 1))."/".(substr($classname, strrpos($classname, '\\') + 1));
		
		date_default_timezone_set("Asia/Karachi");
		Jira::Init($this->server);
		$opt_fields = $this->option('fields');
		Jira::Init($this->server);
		if($opt_fields == 'update')
		{
			$this->ConfigureJiraFields();
			return;
		}
		
		$datetime = new \DateTime();
		$rebuild = $this->option('rebuild');
		if(file_exists($this->datapath."lastemailsent")&&$rebuild==0)
		{
			$dt = file_get_contents($this->datapath."lastemailsent");
			if($dt == $datetime->format('Y-m-d'))
			{
				echo "Todays email already sent\n";	
				return;
			}
		}
		$day = date('D');
		if(($day != 'Mon')&&($day != 'Wed'))
		{
			echo "Not scheduled for ".$day;
			return;
		}
		$hour = date('H');
		if($hour < 11)
		{
			echo "Not yet 11";
			return;
		}
		$beat = $this->option('beat');
		foreach($this->urls as $url)
		{
			$jira_query = '';
			$del = '';
			$data = $this->Get($url);
			$pending_prs = [];
			$repository = null;
			
			foreach($data->values as $pr)
			{
				//dump($pr->title);
				$openpr =  new \StdClass();
				$openpr->title = $pr->title;
				$jira_ref = explode(" ",$pr->title);
				$jira_key = $this->GetJiraKey($jira_ref[0]);
				if($jira_key != null)
					$openpr->jira_key = $jira_key;
				
				if(!isset($openpr->jira_key))
				{
					$jira_ref = explode(":",$pr->title);
					$jira_key = $this->GetJiraKey($jira_ref[0]);
					if($jira_key != null)
						$openpr->jira_key = $jira_key;
				}
				
				if(!isset($openpr->jira_key))
				{
					$path = explode("/",$pr->fromRef->id);
					$jkey = $path[count($path)-1]; 
					$jira_key = $this->GetJiraKey($jkey);
					if($jira_key != null)
					    $openpr->jira_key = $jira_key;
				}
				
				//dump($openpr->title);
				//dump($pr->fromRef->id);
				//if(isset($openpr->jira_key))
				 //  dump($openpr->jira_key);
				//else
				//   dump("none");
				   
				//$openpr->description = $pr->description;
				$dt = new \DateTime();
				$dt->setTimeStamp($pr->createdDate/1000);
				SetTimeZone($dt);
				$minutes = get_working_seconds($dt,new \DateTime());
				//dump ($dt->format('Y-m-d:h:u'));
				$openpr->createdon = $dt->format('Y-m-d');
				$openpr->openduration = seconds2human($minutes);
				$openpr->state = $pr->state;
				$openpr->link = $pr->links->self[0]->href;
				//dump($pr->title);
				
				if(isset($openpr->jira_key))
				{
				   $jira_query .= $del.$openpr->jira_key;
				   $del = ',';
				}
				
				//https://stash.alm.mentorg.com/projects/NUC4/repos/nuc4-tf/pull-requests/76/overview
				//dump("Authors");
				$openpr->author = $pr->author->user->displayName;
				//dump($pr->title);
				//dump($pr->fromRef->id);
				//dump("-----------------");
				$repository = $pr->fromRef->repository->slug;
				//dump("Reviewers");
				$openpr->reviewers = [];
				foreach($pr->reviewers as $reviewer)
				{
					$r =  new \StdClass();
					
					$r->name = $reviewer->user->displayName;
					$r->approved = $reviewer->approved;
					if(!isset($reviewer->user->emailAddress))
					  continue;
					$r->email = $reviewer->user->emailAddress;
					$openpr->reviewers[]= $r;
				}
				$pending_prs[] = $openpr;
			}
		    if(count($pending_prs)>0)
			{
				if($jira_query != '')
				{
					$fields = new Fields($this->key);
					$query = 'key in ('.$jira_query;
					$query .= ")"; 
					//dump($query);
					$tickets =  Jira::FetchTickets($query,$fields);
					
					foreach($tickets as $ticket)
					{
						if($ticket->sprint != '')
						{
							foreach($ticket->sprint as $sprint)
							{
								$sprintdata = explode("[",$sprint)[1];
								$sprintdata = explode(',',$sprintdata);
								$active_sprint=0;
								foreach($sprintdata as $d)
								{
									$keyvalue = explode("=",$d);
									if(($keyvalue[0] == 'state')&&($keyvalue[1] == 'ACTIVE'))
									{
										$active_sprint=1;
									}
									if($active_sprint)
									{
										if($keyvalue[0] == 'name')
											$ticket->activesprintname = $keyvalue[1];
									}
								}
							}
						}
						//if(isset($ticket->activesprintname))
						//	dump($ticket->activesprintname);
						//dump($ticket->statuscategory);
					}
				}
				$datetime = new \DateTime();
				foreach($pending_prs as $pr)
				{
					$pr->activesprintname = null;
					if(isset($pr->jira_key))
					{
						$pr->ticket = $tickets[$pr->jira_key];
						if(isset($pr->ticket->activesprintname))
							$pr->activesprintname = $pr->ticket->activesprintname;
						
			
					}
				}
				usort($pending_prs, [$this,'sortfunc']);
				$html = $this->HtmlFormat($repository,$pending_prs);
				$email =  new Email($this);
				
				$email->Send('Notification:Open PR:NUC:'.$repository,$html);
				
				file_put_contents($this->datapath."lastemailsent",$datetime->format('Y-m-d'));
			}
		}
		echo "Done";
    }
	public function HtmlFormat($repository,$prs)
	{
		$nonpr  = 0;
		$msg = '<h2>'.$repository.'</h2>';	
		foreach($prs as $pr)
		{
		   if(($pr->activesprintname==null)&&($nonpr  == 0))
		   {
			   $nonpr  = 1;
			   $msg .= '<h3>PRs which are not scheduled in current sprint</h3><hr>';
		   }
		   $days = explode("day",$pr->openduration)[0];
		   if($days > 5)
			   $color = 'red';
		   else
			   $color = 'black';
		   $title = $pr->title;
		   if(strlen($pr->title)>60)
		   {
			$title = substr($pr->title, 0,60);
			$title  .= "...";
	       }
		   $msg .= '<div style="float:left;"><a href="'.$pr->link.'">'.$title.'</a></div>';
		   if($pr->activesprintname!=null)
		   {
		       $msg .= '<div style="float:right;"><small>Created by '.$pr->author.' on '.$pr->createdon.'&nbsp&nbsp(<span style="color:'.$color.';">'.$days.' Days old</span>)<br>'.$pr->jira_key.' is scheduled in current sprint - <span style="color:green;">'.$pr->activesprintname.'</span></small></div><br>';
			   //echo $pr->jira_key."\n";
		   }
		   else
		   {
			   $j = 'not found';
			   if(isset($pr->jira_key))
			      $j = $pr->jira_key;
			   $msg .= '<div style="float:right;"><small>Created by '.$pr->author.' on '.$pr->createdon.'&nbsp&nbsp(<span style="color:'.$color.';">'.$days.' Days old</span>)<br> Jira reference is '.$j.'</small></div><br>';
		   }
		   if($pr->activesprintname!=null)
		   {
				$msg .= '<div>Review Status</div>';
				$msg .= '<div>';
				foreach($pr->reviewers as $reviewer)
				{   
					if($reviewer->approved)
					{
						$msg .= '<img  src="cid:checkmark.png" alt="star" width="16" height="16">';
						$msg .= '<span style="color:green;">'.$reviewer->name."&nbsp&nbsp</span>";
					}
					else
					{
						$msg .= '<img src="cid:incomplete.jpg" alt="star" width="16" height="16">';
						$msg .= '<span style="color:orange">'.$reviewer->name."&nbsp&nbsp</span>";
					}
				}
				$msg .= '</div><br>';
		   }
		}
		$msg .= '<br><br><hr>';
	    $msg .= '<small style="margin: auto;">This is an automatically generated email, please do not reply.</small>';
	
		return $msg;
	}
	public function Get($query)
	{
		$data = null;
		$query = str_replace(" ","%20",$query);
		$resource=$query;
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_USERPWD => 'mahmad:NDExMTk0Njk2ODAyOts/IfG8+FgNSlBMKSxk21NIYx/U',
		CURLOPT_URL =>$resource,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if($data != null)
		{
			curl_setopt_array($curl, array(
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $data
				));
		}
		$result = curl_exec($curl);
		
		$code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
		if($code == 200)
			return json_decode($result);
		return 0;
	}
}
