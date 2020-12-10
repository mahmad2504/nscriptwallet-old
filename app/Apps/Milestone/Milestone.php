<?php
namespace App\Apps\Milestone;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Milestone extends App{
	public $timezone='Asia/Karachi';
	public $query='labels in (risk,milestone) and duedate >=';
	public $jira_fields = ['key','status','statuscategory','summary','reporter','assignee','updated','fixVersions']; 
    //public $jira_customfields = ['sprint'=>'Sprint'];  	
	public $jira_server = 'EPS';
	public $cc = 'Mumtaz_Ahmad@mentor.com';
	public $options = 0;
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
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
	}
	function ReadTicket($key)
	{
		$query=['key'=>$key];
		$obj = $this->db->tickets->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function SaveTicket($ticket)
	{
		$query=['key'=>$ticket->key];
		$options=['upsert'=>true];
		$this->db->tickets->updateOne($query,['$set'=>$ticket],$options);
	}
	public function Email($ticket,$delay)
	{
		$email = new Email();
		$duedate = $this->TimestampToObj($ticket->duedate);
		$subject = 'Milestone/Risk/Dependency Notification  ';
		
		$to = [];
		$cc = [];
		$cc[]=$this->cc;
		$cc[]=$ticket->reporter['emailAddress'];
		$cc[]='mumtazahmad2504@gmail.com';
		
		$url = env('JIRA_EPS_URL');
		$ticketurl = '<a href="'.$url.'/browse/'.$ticket->key.'">'.$ticket->key.'</a>';
		
	
		if($delay !=  null)
		{
			$msg = ' Hi, ';
			if($ticket->assignee['name'] == 'unassigned')
			{
				$to[]=$ticket->reporter['emailAddress'];
				$msg .= $ticket->reporter['displayName']."<br><br>";
				$msg .= 'You are receiving this email because Jira ticket '.$ticketurl ." was created by you<br><br>";
				$msg .= '<span style="color:red">'.'This ticket is not assigned to anybody yet'.'</span><br>';
			}
			else
			{
				$to[]=$ticket->assignee['emailAddress'];
				$msg .= $ticket->assignee['displayName']."<br><br>";
				$msg .= 'You are receiving this email because Jira ticket '.$ticketurl." is assigned to you<br>";
			}
			$msg .= '<br><div>Ticket Summary</div>';
			$msg .= '<div style="font-style: italic">'.$ticket->summary.'</div>';
			if($delay > 0)
			{
				$msg .= '<br><span style="font-weight:bold;">This ticket is due on '.$duedate->isoFormat('MMMM Do YYYY').'. In '.$delay.' business days</span><br><br>';
			}
			else if($delay == 0)
			{
				$msg .= '<br><span style="font-weight:bold;">This ticket is due today</span><br><br>';
			}
			else
			{
				$msg .= '<br><span style="color:red">'.'This ticket was due on  '.$duedate->isoFormat('MMMM Do YYYY').'. Now delayed by '.($delay*-1).' business days'.'</span><br><br>';
			}
			$msg .= "If you think deliverable against this ticket cannot be delivered by due date or ticket is mistakenly assigned to you, then please send an email to ticket reporter ".$ticket->reporter['emailAddress']."<br><br>";
		}
		else
		{
			$msg='This ticket '.$ticket->key.' is marked closed today'; 
		}
		$msg .= "<br><br><small>This is an automated email - Please donot reply to this email."; 
		$msg .= "For complete calender please click <a href='http://scripts.pkl.mentorg.com/milestone'>here</a></small><br>";
		$email->Send($this->options['email'],$subject,$msg,$to,$cc);
	}
	public function FetchTickets()
	{
		$start = Carbon::now();
		$start->subDays(90);
		$dt = new \DateTime();
		$this->query = $this->query." ".$start->format('Y-m-d')." ORDER BY duedate ASC";
		return $this->FetchJiraTickets();
	}
	public function Script()
	{
		dump("Running script");
		
		$tickets =  $this->FetchTickets();
		
		$now = $this->CurrentDateTime();
		$cursor = $this->MongoRead('tickets',[]);
		$ticketcount = $this->db->tickets->count([]);
		if($ticketcount == 0)
			$this->options['email'] = 0;
		
		foreach($tickets as $ticket)
		{
			$record = $this->ReadTicket($ticket->key);
			if($record == null)
			{
				$record = new \StdClass();
				$record->updated = 0;
				$record->delay=null;
				$record->duedate=null;
				$record->assignee = [];
				$record->assignee['emailAddress'] = '';
			}
			$duedate = $this->TimestampToObj($ticket->duedate);
			$duedate->hour = 18;
			$duedate->minute = 00;
			$ticket->duedate = $duedate->getTimeStamp();
			if($ticket->statuscategory != 'resolved')
			{
				$delay=0;
				$mul=1;
				$min = $this->GetBusinessMinutes($now,$ticket->duedate,9,18);
				if($min ==0)
				{
					$min = $this->GetBusinessMinutes($ticket->duedate,$now,9,18);
					$delay=1;
					$mul=-1;
				}
				//dump("min = ".$min);
				$str = SecondsToString($min*60,9);
				//dump($str);
				$days = trim(explode('day',$str)[0]);
				$days=$days*$mul;
				if($record->updated != $ticket->updated)
				{
					if(($record->duedate != $ticket->duedate)||($ticket->assignee['emailAddress']!=$record->assignee['emailAddress']))
					{
						dump("Sending email update for ".$ticket->key.". Due in ".$days);
						$this->Email($ticket,$days);
						$ticket->days = $days;
					}
				}
				else if(($days < 8)&&($days%2!=0))
				{
					if($record->days != $days)
					{
						dump("Sending email reminder for ".$ticket->key.". Due in ".$days);
						$this->Email($ticket,$days);
						$ticket->days = $days;
					}
				}
			}
			else // resolved tickets
			{
				if($record->updated != $ticket->updated)
				{
					$ticket->days = 0;
					dump("Sending email closure notice for ".$ticket->key);
					$this->Email($ticket,null);
				}	
			}
			$this->SaveTicket($ticket);
		}
	}
}