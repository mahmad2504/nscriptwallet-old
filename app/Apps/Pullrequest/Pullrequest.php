<?php
namespace App\Apps\Pullrequest;
use App\Apps\App;
use Carbon\Carbon;
use App\Email;

class Pullrequest extends App{
	public $timezone='Asia/Karachi';
	public $query='labels in (risk,milestone) and duedate >=';
	public $jira_fields = ['key','status','statuscategory','summary','resolution','resolutiondate','updated','issuetype','fixVersions'];
	public $jira_customfields = ['sprint'=>'Sprint'];  	
	public $jira_server = 'EPS';
	public $urls = [
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tf/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-docs/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-source/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-packaging/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/nuc4/repos/nuc4-tests/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/QA/repos/bspvk/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/NUMET/repos/scaptic-automation/pull-requests',
	'http://stash.alm.mentorg.com/rest/api/1.0/projects/NUMET/repos/scaptic-framework/pull-requests',
	];
	public $cc='Waqar_Humayun@mentor.com';
	public $escalate='Rizwan_Rasheed@mentor.com';
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		//$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=60)
	{
		$now = Carbon::now($this->timezone);
		$lastemailsenton = $this->app->Read('lastemailsenton');
		if($now->format('Y-m-d') == $lastemailsenton)
		{
			dump("Email already sent today");
			return false;
		}
		if($now->format('H')<10)
			return false;
		return parent::TimeToRun($update_every_xmin);
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
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
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
	    $msg .= '<small style="margin: auto;">This is an automatically generated email, please do not reply. You are getting these emails because you are one of reviewers of above pull requests</small><br>';
		return $msg;
	}
	public function Script()
	{
		dump("Running script");
		foreach($this->urls as $url)
		{
			$to =[];
			$cc =[];
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
				$dt = CDateTime($pr->createdDate/1000,$this->timezone);
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
				$openpr->author = $pr->author->user->displayName;
				$repository = $pr->fromRef->repository->slug;
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
					$query = 'key in ('.$jira_query;
					$query .= ")"; 
					//dump($query);
					$tickets =  $this->FetchJiraTickets($query);
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
						$cc[$this->escalate]=$this->escalate;
					}
				}
				$cc[$this->cc] = $this->cc;
				usort($pending_prs, [$this,'sortfunc']);
				$html = $this->HtmlFormat($repository,$pending_prs);
				$to['mumtaz_ahmad@mentor.com']='mumtaz_ahmad@mentor.com';
				$to = array_values($to);
				$cc = array_values($cc);
				$email = new Email();
				$subject = 'Notification:Open PR:NUC:'.$repository;
				$email->AddAttachement('public/apps/Pullrequest/checkmark.png');
				$email->AddAttachement('public/apps/Pullrequest/incomplete.jpg');
				dump('Email sent for '.$repository);
				$email->Send($this->options['email'],$subject,$html,$to,$cc);
				//$this->SendEmail($html,'Notification:Open PR:NUC:'.$repository,$to,$cc);
			}
		}
		$now = Carbon::now($this->timezone); 
		$lastemailsenton = $this->app->Save(['lastemailsenton'=>$now->format('Y-m-d')]);
	}
}