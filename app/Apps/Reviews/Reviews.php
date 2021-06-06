<?php
namespace App\Apps\Reviews;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Reviews extends App{
	public $timezone='Asia/Karachi';
	public $query='labels in (Review_Request) and statusCategory != Done';
	public $jira_fields = ['key','status','description','summary','updated','created','assignee','reporter','duedate']; 
	public $jira_server = 'EPS';
	public $scriptname = 'reviews';
	public $options = 0;
	public function __construct($options=null)
        {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);

       }
	public function TimeToRun($update_every_xmin=20)
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
	public function Rebuild()
	{
		$this->db->tickets->drop();
		//$this->options['email']=0;// no emails when rebuild
	}
	public function SaveTicket($ticket)
	{
		$query = ['key'=>$ticket->key];
		$options=['upsert'=>true];
		$this->db->tickets->updateOne($query,['$set'=>$ticket],$options);
	}
	public function ReadTicket($key)
	{
		$query = ['key'=>$key];
		return $this->db->tickets->findOne($query);
	}
	public function HtmlFormat($ticket)
	{
		$dt = CDateTime($ticket->created,$this->timezone);
		$elapsedtime = GetBusinessSeconds($dt,new \DateTime(),9,18);
		$elapsedtime = SecondsToString($elapsedtime,8);
		$days = explode("day",$elapsedtime)[0];
		$msg = '<h2>Review Request - '.$ticket->key.'</h2><p>'.$ticket->summary.'</p>';	
		if($days > 5)
			$color = 'red';
		else
			$color = 'black';
		$created = $dt->format('Y-m-d');
		$msg .= '<div style="float:right;"><small>Created by '.$ticket->reporter['displayName'].' on '.$created.'&nbsp&nbsp(<span style="color:'.$color.';">'.$days.' Business Days old</span>)<br> Jira reference is <a href="'.$ticket->url.'">'.$ticket->key.'</a></small></div><br>';
		
		$msg .= '<div>Review Status</div><br>';
		$msg .= '<div>';
		foreach($ticket->reviewers as $reviewer)
		{   
			if($reviewer->approved_on)
			{
				$msg .= '<img  src="cid:checkmark.png" alt="star" width="16" height="16">';
				$msg .= '<span style="color:green;">'.$reviewer->displayname."  signed off on <small>".$reviewer->approved_on."</small>&nbsp&nbsp</span>";
			}
			else
			{
				$msg .= '<img src="cid:incomplete.jpg" alt="star" width="16" height="16">';
				$msg .= '<span style="color:orange">'.$reviewer->displayname."&nbsp&nbsp</span>";
			}
			$msg .= '<br>';
			
		}
		$msg .= '</div><br>';
		if($ticket->duedate == '')
		{
			//$msg .= '<div>There is no due date</div>';
		}
		else
		{
			$duedate = $this->TimestampToObj($ticket->duedate);
			if($duedate->isPast())
				$duein = GetBusinessSeconds($duedate,new \DateTime(),9,18);
			else
				$duein = GetBusinessSeconds(new \DateTime(),$duedate,9,18);
			$duein = SecondsToString($duein,8);
			$dueindays = explode("day",$duein)[0];
			if($duedate->isPast())
				$msg .= '<div style="color:red">Ticket was due on '.$duedate->format('Y-m-d').'  ('.$dueindays.' Business days ago) </div>';
			else
				$msg .= '<div style="color:green">Ticket due date is <small>'.$duedate->format('Y-m-d').'</small>  (in '.$dueindays.' Business days) </div>';
		}
		$msg .= '<br><br><hr>';
	    $msg .= '<small style="margin: auto;">This is an automatically generated email, please do not reply. You are getting these emails because you are one of reviewers of <a href="'.$ticket->url.'">'.$ticket->key.'</a></small><br>';
		return $msg;
	}
	public function Script()
	{
		dump("Running script");
		$url = env('JIRA_'.$this->jira_server.'_URL')."/browse/";
		$lut = $this->ReadUpdateTime();
		if($lut != null)
		{
			$lut = new \DateTime($this->ReadUpdateTime());
			$lut = Carbon::instance($lut);
			$isupdatedtoday = $lut->isToday();
		}
		else
			$isupdatedtoday = 0;
		
		$now = Carbon::now($this->timezone);
		
		
		$tickets =  $this->FetchJiraTickets();
		foreach($tickets as $ticket)
		{
			echo "Processing ".$ticket->key."\n";
			$ticket->url = $url.$ticket->key;
			$sticket = $this->ReadTicket($ticket->key);
			
			$desc = strtolower($ticket->description);
			$desc = explode("\r\n",$desc);
			$valid=0;
			$users = [];
			foreach($desc as $line)
			{
				$parts = explode('reviewer',$line);
				if(count($parts)==2)
				{
					$parts = explode('~',$parts[1]);
					if(count($parts)==2)
					$parts = explode(']',$parts[1]);
					else
						continue;
					
					if(!array_key_exists($parts[0],$users))
					{
						$u =  new \StdClass();
						$u->name = $parts[0];
						$users[$u->name] = $u;
						$u->approved_on = 0;
					}
					else 
						$u = $users[$parts[0]];
					
					$ticket->reviewers[$u->name] = $u;
					$valid=1;
				}
				else
				{
					$parts = explode('cc',$line);
					//dump($ticket->key);
					//dump($parts);
					if(count($parts)==2)
					{
						$parts = explode('~',$parts[1]);
						if(!isset($parts[1]))
						  continue;
						$parts = explode(']',$parts[1]);
						
						if(!array_key_exists($parts[0],$users))
						{
							$u =  new \StdClass();
							$u->name = $parts[0];
							$users[$u->name] = $u;
							$u->approved_on = 0;
						}
						else 
							$u = $users[$parts[0]];
					
						$ticket->cc[$u->name] = $u;
						$users[$u->name] = $u;
					}
				}
			}
			if($valid)
			{
				$comments = $this->FetchComments($ticket->key);
				foreach($comments->comments as $comment)
				{
					if(substr( strtolower($comment->body), 0, 8 ) === "approved")
					//if(( strtolower($comment->body)=='approved')||(strtolower($comment->body)=='approved.'))
					{
						if(!array_key_exists($comment->author->name,$users))
						{
							$u =  new \StdClass();
							$u->name = $comment->author->name;
							$users[$u->name] = $u;
						}
						else 
							$u = $users[$comment->author->name];
						$u->approved_on = explode("T",$comment->created)[0];
						$ticket->approvals[$u->name] = $u;
					}
				}
				if(isset($ticket->approvals))
					$ticket->approvals =  array_values($ticket->approvals);
				if(isset($ticket->cc))
					$ticket->cc =  array_values($ticket->cc);
				if(isset($ticket->reviewers))
					$ticket->reviewers =  array_values($ticket->reviewers);
				
				$users =  array_values($users);
				foreach($users as $user)
				{
					$user_details = $this->FetchUser($user->name);
					if($user_details != null)
					{
						$user->displayname = $user_details->displayName;
						if(isset($user_details->emailAddress))
						{
							$user->email = $user_details->emailAddress;
							
						}
						else
							$user->email = '';
					}
				}
				$this->SaveTicket($ticket);
				
				$html = $this->HtmlFormat($ticket);
				$email = new Email();
				if($sticket == null)
				{
					$subject = 'Notification:Review Request - Created:'.$ticket->key;
				}
				else if($sticket->updated != $ticket->updated)
				{
					$subject = 'Notification:Review Request - Updated:'.$ticket->key;
				}
				else
				{
					//if($isupdatedtoday)
					//	continue;
					$subject = 'Notification:Review Request - Reminder:'.$ticket->key;
					
					if($this->options['force']==0)
					{
					if($now->isMonday()||$now->isThursday()) 
					{
							if($isupdatedtoday)
							{
								if($this->options['force']==0)
									continue;
							}
					}
					else
							continue;
					}
				}
				if($this->options['force']==0)
				{ }
				else if($this->options['key']!=$ticket->key)
				{
					continue;
				}
					
				$email->AddAttachement('public/apps/Reviews/checkmark.png');
				$email->AddAttachement('public/apps/Reviews/incomplete.jpg');
				$reviewer = [];
				foreach($ticket->reviewers as $r)
				{
					if($r->email != '')
						$reviewer[] = $r->email;
				}
				$cc = [];
				$cc[] = $ticket->reporter['emailAddress'];
				if(!isset($ticket->cc))
					$ticket->cc = [];
				
				foreach($ticket->cc as $c)
				{
					if($c->email != '')
						$cc[] = $c->email;
				}
				
				$email->Send($this->options['email'],$subject,$html,$reviewer,$cc);
				
			}
		}
	}
}