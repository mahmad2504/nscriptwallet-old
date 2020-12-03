<?php

namespace App\Console\Commands\Pullrequest;

use Illuminate\Console\Command;
use App\Apps\Pullrequest\Pullrequest;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use App\Email;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'pullrequest:sync {--rebuild=0}} {--beat=0}';

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
	public function ConfigureJiraFields($fields)
	{
		dump("Configuring Jira fields");
		$fields->Set(['key','status','statuscategory','summary','resolution','resolutiondate','updated','issuetype','fixVersions','issuetypecategory']);
		$fields->Set(['sprint'=>'Sprint']);
		$fields->Dump();
	}
    public function Permission()
	{
		$this->app = new PullRequest();
		$rebuild = $this->option('rebuild');
		if($rebuild == 1)
			return true;
		return true;
		return $this->app->Permission();
	}
	public function Preprocess()
	{
		
	}
	public function Postprocess()
	{
		$this->app->SaveUpdateTime();

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
	private  function sortfunc($a,$b) 
	{
	   if(($a->activesprintname != null)&&($b->activesprintname != null))
	      return 0;
	   if($a->activesprintname != null)
		return -1;
	   if($b->activesprintname != null)
		return 1;
	}
	public function SendEmail($msg,$subject,$to,$cc)
	{
		$email = new Email('localhost',['support-bot@mentorg.com', 'Support Bot'],$this->app->email_from);
		$subject = $subject;
		$email->AddTo($to);
		$email->AddCC($cc);
		$email->AddAttachement('public/apps/Pullrequest/checkmark.png');
		$email->AddAttachement('public/apps/Pullrequest/incomplete.jpg');
		$email->Send($subject,$msg);
	}
	public function HtmlFormat($repository,$prs)
	{
		$nonpr  = 0;
		$msg = '<h2>Pull Requests - '.$repository.'</h2>';	
		//$msg .= '<p style="background-color:yellow">Resending for this week due to duplications in earlier report</p>';	
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
		       $msg .= '<div style="float:right;"><small>Created by '.$pr->author.' on '.$pr->createdon.'&nbsp&nbsp(<span style="color:'.$color.';">'.$days.' Business days old</span>)<br>'.$pr->jira_key.' is scheduled in current sprint - <span style="color:green;">'.$pr->activesprintname.'</span></small></div><br>';
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
	    $msg .= '<small style="margin: auto;">This is an automatically generated email, please do not reply. You are getting these emails because you are one of reviewers of one of pull requests</small>';
		return $msg;
	}
    public function handle()
    {
		if(!$this->Permission(2))
		{
			echo "Not permitted at this time\n";
			return;
		}
		$app = $this->app;
		$this->Preprocess();
		////////////////////////////////////////////////////////////////
		Jira::Init('EPS');
		$fields = new Fields($app);
		if(!$fields->Exists())
			$this->ConfigureJiraFields($fields);
		
		
		foreach($app->urls as $url)
		{
			$to =[];
			$cc =[];
			$jira_query = '';
			$del = '';
			$data = $app->Get($url);
			$pending_prs = [];
			$repository = null;
			
			foreach($data->values as $pr)
			{
				dump($pr->title);
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
				$dt = CDateTime($pr->createdDate/1000,$app->timezone);
				//$dt = new \DateTime();
				//$dt->setTimeStamp($pr->createdDate/1000);
				//SetTimeZone($dt);
				//$minutes = get_working_seconds($dt,new \DateTime());
				//dump ($dt->format('Y-m-d:h:u'));
				$duration = GetBusinessSeconds($dt,new \DateTime(),9,18);
				$openpr->createdon = $dt->format('Y-m-d');
				$openpr->openduration = SecondsToString($duration,8);
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
			$escalate=0;
			if(count($pending_prs)>0)
			{
				if($jira_query != '')
				{
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
					}
				}				
				foreach($pending_prs as $pr)
				{
					$pr->activesprintname = null;
					if(isset($pr->jira_key))
					{
						$pr->ticket = $tickets[$pr->jira_key];
						if(isset($pr->ticket->activesprintname))
							$pr->activesprintname = $pr->ticket->activesprintname;
					}
					
					foreach($pr->reviewers as $r)
						$to[$r->email] = $r->email;
					$days = explode("day",$pr->openduration)[0];
					if($days > 5)
					{
						$escalate=0;
						$cc[$app->email_escalate]=$app->email_escalate;
					}
				}
				$cc[$app->email_cc] = $app->email_cc;
				usort($pending_prs, [$this,'sortfunc']);
				$html = $this->HtmlFormat($repository,$pending_prs);
				$to = array_values($to);
				//dump($to);
				$cc = array_values($cc);
				//foreach($to as $t)
				//	$html .= '<p>'.$t.'</p>';
				//dump($cc);
				//$this->SendEmail($html,'Notification:Open PR:NUC:'.$repository,'mumtaz_ahmad@mentor.com',[]);
				
				
				//$to=[];
				
				//$to[] = 'mumtaz_ahmad@mentor.com';
				//$to[] = 'mumtazahmad2504@gmail.com';
				//dump($cc);
				//$cc =[];
				$to[] = 'mumtaz_ahmad@mentor.com';
				//$cc[] = 'mumtazahmad2504@gmail.com';
				dump($to);
				dump($cc);
				$this->SendEmail($html,'Notification:Open PR:NUC:'.$repository,$to,$cc);
				//dd($pending_prs);
				//$html = $this->HtmlFormat($repository,$pending_prs);
				//$email =  new Email($this);
				
				//$email->Send('Notification:Open PR:NUC:'.$repository,$html);
				
				//file_put_contents($this->datapath."lastemailsent",$datetime->format('Y-m-d'));
			}
		}
		$this->Postprocess();
    }
}
