<?php
namespace App\Apps\Support;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use App\Email;
class Support extends App{
	public $timezone='America/Chicago';
	public $to='dan_schiro@mentor.com';
	public $bcc='mumtaz_ahmad@mentor.com';
	public $query='project=Siebel_JIRA AND "Product Name" !~ Vista AND "Product Name" !~ A2B AND "Product Name" !~ XSe and updated >= "2020-01-01" order by key';			 
	public $jira_fields = ['issuelinks','resolution','issuetype','assignee','priority','key','summary','status','statuscategory','resolutiondate','created','updated','transitions'];
	public $jira_customfields =['premium_support'=>'Premium Support',
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
				'account'=>'Account'];		
	public $jira_server = 'EPS';
	public $start_hour=8;   // Hour on which business day start
	public $end_hour=20;    // Hour on which business day end
	public $hours_day=12;   // Total hours in 1 business day
	private $sla= ['critical'=>[2,1],
				 'high'=>[10,5],
				 'medium'=>[20,10],
				 'low'=>[40,20],
				 ''=>[40,20]];
	private $sla_firstcontact = [2,1];
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		$this->email = new Email();
		$this->email->AddBCC($this->bcc);
		$this->email->AddTo($this->to);
		
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=30)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		$this->db->tickets->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	
	
	function ClosedTickets()
	{
		$date = new \DateTime("-6 months");
		$query = ['statuscategory' => 'resolved','resolutiondate'=>['$gte'=>$date->getTimestamp()]];
		$options = ['sort' => ['resolutiondate' => -1],
				    //'limit' => 50 ,
					'projection' => ['_id' => 0]];

		$cursor = $this->db->tickets->find($query,$options);
		$tickets = $cursor->toArray();
		return $tickets;
	}
	function ActiveTickets()
	{
		$query = ['statuscategory' => ['$ne' =>'resolved']];
		$options = ['sort' => ['updated' => -1],
					'projection' => ['_id' => 0]];
		$cursor = $this->db->tickets->find($query,$options);
		$tickets = $cursor->toArray();
		return $tickets;
	}
	function UpdatedTickets()
	{
		$query = [];
		$options = [
					'projection' => ['_id' => 0],
					'sort' => ['updated' => -1],
				    'limit' => 100 ,
					'projection' => ['_id' => 0]];

		$cursor =$this->db->tickets->find($query,$options);
		$tickets = $cursor->toArray();
		return $tickets;
	}
	function SaveTicket($ticket)
	{
		$options=['upsert'=>true];
		$query=['key'=>$ticket->key];
		$options=['upsert'=>true];
		$this->db->tickets->updateOne($query,['$set'=>$ticket],$options);
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
	public function SendFirstContactEmail($ticket)
	{
		$this->email->to[] = $ticket->assignee->emailAddress;
		$rem = 100-$ticket->percent_first_contact_time_consumed;
		$quota = SecondsToString($ticket->firstcontact_minutes_quota*60,$this->hours_day);
	
		if($rem <= 0)
		{
			$subject = 'Support SLT Violation!!';
			$msg = 'This is an automated alert for ';
			$msg = '<span style="font-weight:bold">'.$ticket->key.'</span><br><br>';	
			$msg .= 'This ticket has crossed the SLT Threshold for "First Contact"<br>';
			$msg .= 'Please contact Dan Schiro for any questions.';
		}
		else
		{
			$subject = 'Support SLT Notification!!';
			$msg = '<span style="font-weight:bold">'.$ticket->key.'</span> is approaching a SLT milestone<br>';
			$msg .= '<p>'.$rem.' % of '.$quota.' remains on milestone "First Contact"<br>';
			$msg .= '<p>';// style="font-style: italic;">';
			$msg .= 'This is an automated message.Contact Dan Schiro for any questions.</p>';
		}
		$this->email->Send($this->options['email'],$subject,$msg,$this->to);
	}
	public function SendFirstContactNotification($ticket)
	{
		if(!isset($ticket->first_contact_alert))
		{
			$ticket->first_contact_alert=0;
		}
			
		if($ticket->first_contact_date == '')
		{
			$ticket->percent_first_contact_time_consumed = 100	;
			if($ticket->net_minutes_to_firstcontact <= $ticket->firstcontact_minutes_quota)
				$ticket->percent_first_contact_time_consumed = round($ticket->net_minutes_to_firstcontact/$ticket->firstcontact_minutes_quota*100,1);
		}
		else
			return ;
			
		if($ticket->percent_first_contact_time_consumed >= 100)
		{
			if($ticket->first_contact_alert<5)
			{
				$this->SendFirstContactEmail($ticket);
				$ticket->first_contact_alert=5;
			}
		}
		else if($ticket->percent_first_contact_time_consumed >= 90)
		{
			if($ticket->first_contact_alert<4)
			{
				if($email) $this->SendFirstContactEmail($ticket);
				$ticket->first_contact_alert=4;
			}
		}
		else if($ticket->percent_first_contact_time_consumed >= 75)
		{
			if($ticket->first_contact_alert<3)
			{
				$this->SendFirstContactEmail($ticket);
				$ticket->first_contact_alert=3;
			}
		}
		else if($ticket->percent_first_contact_time_consumed >= 50)
		{
			if($ticket->first_contact_alert<2)
			{
				$this->SendFirstContactEmail($ticket);
				$ticket->first_contact_alert=2;
			}
		}
		else if($ticket->percent_first_contact_time_consumed >= 25)
		{
			if($ticket->first_contact_alert<1)
			{
				$this->SendFirstContactEmail($ticket);
				$ticket->first_contact_alert=1;
			}
		}
	}
	public function SendResolutionTimeEmail($ticket)
	{
		$this->email->to[] = $ticket->assignee->emailAddress;
		$quota = SecondsToString($ticket->minutes_quota*60,$this->hours_day);
		
		$rem = 100-$ticket->percent_time_consumed;
		if($rem <= 0)
		{
			$subject = 'Support SLT Violation!!';
			$msg = '<span style="font-weight:bold">'.$ticket->key.'</span><br><br>';
			$msg .= 'This ticket has crossed the SLT Threshold of '.$quota.' for "Time to Resolution"<br>';
			$msg .= 'Please contact Dan Schiro for any questions.';
		}
		else
		{
			$subject = 'Support SLT Notification!!';
			$msg = '<span style="font-weight:bold">'.$ticket->key.'</span> is approaching a SLT milestone<br>';
			$msg .= '<p>'.$rem.' % of '.$quota.' remains on milestone "Time to Resolution"<br>';
			$msg .= '<p>';// style="font-style: italic;">';
			$msg .= 'This is an automated message.Contact Dan Schiro for any questions.</p>';
		}
		$this->email->Send($this->options['email'],$subject,$msg);
	}
	public function SendTimeToResolutionNotification($ticket)
	{
		if(!isset($ticket->time_to_resolution_alert))
		{
			$ticket->time_to_resolution_alert = 0;
		}
		if($ticket->percent_time_consumed >= 100)
		{
			if($ticket->time_to_resolution_alert<5)
			{
				$this->SendResolutionTimeEmail($ticket);
				$ticket->time_to_resolution_alert=5;
			}
		}
		else if($ticket->percent_time_consumed >= 90)
		{
			if($ticket->time_to_resolution_alert<4)
			{
				$this->SendResolutionTimeEmail($ticket);
				$ticket->time_to_resolution_alert=4;
			}
		}
		else if($ticket->percent_time_consumed >= 75)
		{
			if($ticket->time_to_resolution_alert<3)
			{
				$this->SendResolutionTimeEmail($ticket);
				$ticket->time_to_resolution_alert=3;
			}
		}
		else if($ticket->percent_time_consumed >= 50)
		{
			if($ticket->time_to_resolution_alert<2)
			{
				$this->SendResolutionTimeEmail($ticket);
				$ticket->time_to_resolution_alert=2;
			}
		}
		else if($ticket->percent_time_consumed >= 25)
		{
			if($ticket->time_to_resolution_alert<1)
			{
				$this->SendResolutionTimeEmail($ticket);
				$ticket->time_to_resolution_alert=1;
			}
		}
	}
	public function SetSlaQuota($ticket)
	{
		$priority = '';
		switch(strtolower($ticket->priority->name))
		{
			case 'critical':
				$priority = 'critical';
				break;
			case 'high':
				$priority = 'high';
				break;
			case 'medium':
				$priority = 'medium';
				break;
			case 'low':
				$priority = 'low';
				break;
			default:
				$priority = '';	
				break;
		}
		$ticket->sla=$this->sla[$priority][$ticket->premium_support];
		$ticket->firstcontact_minutes_quota=$this->sla_firstcontact[$ticket->premium_support]*$this->hours_day*60;
		$ticket->minutes_quota=$ticket->sla*$this->hours_day*60;	
		
	}
	public function ComputeWaitingTime($ticket)
	{
		$interval = null;
		$intervals = [];
		if($ticket->test_case_provided_date == null)
		{
			$ticket->waitminutes = 0;
			return 0;
		}
		foreach($ticket->transitions as $transition)
		{
			if($ticket->test_case_provided_date >  $transition->created )
			{
				 $transition->created = $ticket->test_case_provided_date;
			}
			if($ticket->solution_provided_date != '')
			{
				if($ticket->solution_provided_date < $transition->created)
					$transition->created = $ticket->solution_provided_date;
			}
			
			if(($transition->to == "Waiting Customer Feedback")||($transition->to == "Queued"))
			{
				if(($transition->from == "Waiting Customer Feedback")||($transition->from == "Queued"))
				{
					
				}
				else
				{
					$interval = new \StdClass();
					$interval->start = $transition->created;
					$interval->end  = $this->CurrentDateTime();
					if($ticket->solution_provided_date != '')
					{
						$interval->end = $ticket->solution_provided_date;					
					}
					$interval->waiting_minutes = $this->GetBusinessMinutes($interval->start,$interval->end,$this->start_hour,$this->end_hour);
					continue;
				}
			}
			else if(($transition->from == "Waiting Customer Feedback")||($transition->from== "Queued"))
			{
				if($interval != null)
				{
					$interval->end = $transition->created;
					
					$interval->waiting_minutes = $this->GetBusinessMinutes($interval->start,$interval->end,$this->start_hour,$this->end_hour);
					$interval->type="customer wait";
					$intervals[] = $interval;
					$interval = null;
				}
			}
			if($transition->to=="Resolved")
			{
				$interval = new \StdClass();
				$interval->start = $transition->created;
				$interval->waiting_minutes = 0;
			}
			
			if(($transition->from=="Resolved")  && ($transition->to == "Reopened"))
			{
				$interval->end = $transition->created;
				$interval->waiting_minutes = $this->GetBusinessMinutes($interval->start,$interval->end,$this->start_hour,$this->end_hour);
				$interval->type="Reopen";
				$intervals[] = $interval;
				$interval = null;
			}
		}
		if($interval != null && $interval->waiting_minutes>0)
			$intervals[] = $interval;
		
		$waiting_minutes = 0;
		
		foreach($intervals as $interval)
		{
			if($interval->waiting_minutes <=0 )
				continue;
			$waiting_minutes  += $interval->waiting_minutes;
		}
		
		//dump($ticket->key."  ".$waiting_minutes);
		$ticket->waitminutes = $waiting_minutes;
		return $waiting_minutes;
	}
	public function UpdateFirstContactDelay($ticket)
	{
		if($ticket->first_contact_date != '')
		{
			$ticket->net_minutes_to_firstcontact = $this->GetBusinessMinutes($ticket->created,$ticket->first_contact_date,$this->start_hour,$this->end_hour);
		}
		else
		{
			$now =  $this->CurrentDateTime();
			$ticket->net_minutes_to_firstcontact = $this->GetBusinessMinutes($ticket->created,$now,$this->start_hour,$this->end_hour);
		}
		$ticket->net_time_to_firstcontact = SecondsToString($ticket->net_minutes_to_firstcontact*60,$this->hours_day);		
		if($ticket->firstcontact_minutes_quota<$ticket->net_minutes_to_firstcontact)
		{
			if($ticket->violation_firstcontact == 0)
			{
				//JiraTicket::UpdateCustomField($ticket->key,'violation_firstcontact',1);
			}
			$ticket->violation_firstcontact = 1;
		}
		else
		{
			if($ticket->violation_firstcontact == 1)
			{
				//JiraTicket::UpdateCustomField($ticket->key,'violation_firstcontact',0);
			}
			$ticket->violation_firstcontact = 0;
		}
		
		//echo $ticket->net_minutes_to_firstresponse."\n";
		//dd($ticket->created)."\n";
		
	}
	public function UpdateNetTimeToResolution($ticket)
	{
		$ticket->net_minutes_to_resolution = 0;
		$ticket->net_time_to_resolution = '';
		if($ticket->test_case_provided_date != '')
		{
			if(($ticket->solution_provided_date != '')||($ticket->resolutiondate != ''))// Ticket net resoluton time closedir
			{
				if($ticket->solution_provided_date != '')
				     $finish = $ticket->solution_provided_date;
				else
				     $finish =  $ticket->resolutiondate;
			}
			else
				$finish = $this->CurrentDateTime();
			
			$test_case_provided_date = $ticket->test_case_provided_date;
			//echo $test_case_provided_date."\n";
			//echo $finish."\n";
			$ticket->net_minutes_to_resolution = $this->GetBusinessMinutes($test_case_provided_date,$finish,$this->start_hour,$this->end_hour);
			
			$ticket->net_minutes_to_resolution = $ticket->net_minutes_to_resolution - $ticket->waitminutes ;
			$ticket->net_time_to_resolution  = SecondsToString($ticket->net_minutes_to_resolution*60,$this->hours_day);		
		}
	}
	public function UpdateTimeToResolution($ticket)
	{
		if(($ticket->resolutiondate != '')&&($ticket->first_contact_date != ''))
		{
			//dump($ticket->resolutiondate);
			//dump($ticket->first_contact_date);
			//$ticket->net_minutes_to_resolution = $this->GetBusinessMinutes($ticket->first_contact_date,$ticket->resolutiondate,$this->start_hour,$this->end_hour);
			$difference = $ticket->resolutiondate - $ticket->first_contact_date;
			//dump($difference/60);
			//echo "1 -".$ticket->net_minutes_to_resolution."\n";
			//echo "1\n";
			
		}
		else if(($ticket->resolutiondate == ''))
		{
			$now =  $this->CurrentDateTime();
			//dump($ticket->created);
			$difference =$now-$ticket->created;
			//$ticket->net_minutes_to_resolution = $this->GetBusinessMinutes($ticket->created,$now,$this->start_hour,$this->end_hour);
			//echo "2 -".$ticket->net_minutes_to_resolution."\n";
		}
		if(!isset($ticket->net_minutes_to_resolution))
		{
			//$ticket->net_minutes_to_resolution = 0;
			//echo "3 -".$ticket->net_minutes_to_resolution."\n";
		}
		//$ticket->net_minutes_to_resolution = $ticket->net_minutes_to_resolution - $ticket->waitminutes ;
		//echo "4 -".$ticket->net_minutes_to_resolution."\n";
		//$ticket->net_time_to_resolution  = SecondsToString($ticket->net_minutes_to_resolution*60,$this->hours_day);	
		
		if(isset($difference))
		{
			$ticket->gross_minutes_to_resolution=round($difference/60);
			$ticket->gross_time_to_resolution=SecondsToString($difference,24);
			//$ticket->gross_time_to_resolution=$difference->days." days,".$difference->h." hours,".$difference->i." minutes";
		}
		else
		{
			$ticket->gross_minutes_to_resolution = 0;	
			$ticket->gross_time_to_resolution = '';
		}
		$this->UpdateNetTimeToResolution($ticket);
		
		$ticket->percent_time_consumed = 100;
		if($ticket->net_minutes_to_resolution < $ticket->minutes_quota)
			$ticket->percent_time_consumed = round($ticket->net_minutes_to_resolution/$ticket->minutes_quota*100,1);
		
		if($ticket->percent_time_consumed>=100)
		{
			if($ticket->violation_time_to_resolution == 0)
			{
				//JiraTicket::UpdateCustomField($ticket->key,'violation_time_to_resolution',1);
			}
			$ticket->violation_time_to_resolution = 1;
		}
		else
		{
			if($ticket->violation_time_to_resolution == 1)
			{
				//JiraTicket::UpdateCustomField($ticket->key,'violation_time_to_resolution',0);
			}
			$ticket->violation_time_to_resolution = 0;
		}
	}
	function Debug($ticket)
	{
		//echo $ticket->key."FMQ".$ticket->firstcontact_minutes_quota." MQ".$ticket->minutes_quota;
		//echo " WT=".$ticket->waitminutes." GM".$ticket->gross_minutes_to_resolution."\n";
		$checkdata =[];
		$search=$ticket->key;
		$lines = file('D:\xampp\htdocs\support_sla\out.txt');
		foreach($lines as $line)
		{
		  // Check if the line contains the string we're looking for, and print if it does
		  if(strpos($line, $search) !== false)
		  {
			$checkdata = explode(",",$line);
		  }
		}
		if(isset($checkdata[0]))
		{
			if($ticket->key != $checkdata[0])
			   echo $ticket->key." Key not equal\n";
			if($ticket->firstcontact_minutes_quota != $checkdata[1])
			   echo $ticket->key." firstcontact_minutes_quota ,mismatch ".$checkdata[1]." ? ".$ticket->firstcontact_minutes_quota;
			if($ticket->minutes_quota != $checkdata[2])
			  echo $ticket->key." minutes_quota ,mismatch ".$checkdata[2]." ? ".$ticket->minutes_quota."\n";
			if($ticket->premium_support != $checkdata[3])
			   echo $ticket->key." premium_support ,mismatch ".$checkdata[3]." ? ".$ticket->premium_support."\n";
			if($ticket->sla != $checkdata[4])
			   echo $ticket->key." sla mismatch priority is ".$ticket->priority->name." premium support is ".$ticket->premium_support."  ".$checkdata[4]." ? ".$ticket->sla."\n";
			if($ticket->waitminutes != $checkdata[5])
			   echo $ticket->key." waitminutes mismatch ".$checkdata[5]." ? ".$ticket->waitminutes."\n";
			if($ticket->test_case_provided_date != $checkdata[6])
			   echo $ticket->key." test_case_provided_date mismatch ".$checkdata[6]." ? ".$ticket->test_case_provided_date."\n";
			if($ticket->resolutiondate != $checkdata[7])
			   echo $ticket->key." resolutiondate mismatch ".$checkdata[7]." ? ".$ticket->resolutiondate."\n";
			if($ticket->net_minutes_to_resolution != $checkdata[8])
			   echo $ticket->key." net_minutes_to_resolution mismatch ".$checkdata[8]." ? ".$ticket->net_minutes_to_resolution."\n";
			if($ticket->net_time_to_resolution != $checkdata[9])
			   echo $ticket->key." net_time_to_resolution mismatch ".$checkdata[9]." ? ".$ticket->net_time_to_resolution."\n";
			if($ticket->solution_provided_date != $checkdata[10])
			   echo $ticket->key." solution_provided_date mismatch ".$checkdata[10]." ? ".$ticket->solution_provided_date."\n";
			
			if($ticket->gross_minutes_to_resolution != $checkdata[11])
			   echo $ticket->key." gross_minutes_to_resolution mismatch (".$ticket->resolutiondate.")".$checkdata[11]." ? ".$ticket->gross_minutes_to_resolution."\n";
			
			if($ticket->created != $checkdata[12])
			   echo $ticket->key." created mismatch ".$checkdata[12]." ? ".$ticket->created."\n";
			
			if($ticket->first_contact_date != $checkdata[13])
			   echo $ticket->key." first_contact_date mismatch ".$checkdata[13]." ? ".$ticket->first_contact_date."\n";
			
			if($ticket->gross_time_to_resolution != $checkdata[14])
			   echo $ticket->key." gross_time_to_resolution mismatch ".$checkdata[14]." ? ".$ticket->gross_time_to_resolution."\n";
			
			if($ticket->percent_time_consumed != $checkdata[15])
			   echo $ticket->key." percent_time_consumed mismatch ".$checkdata[15]." ? ".$ticket->percent_time_consumed."\n";
			
			if($ticket->violation_time_to_resolution != $checkdata[16])
			   echo $ticket->key." violation_time_to_resolution mismatch ".$checkdata[16]." ? ".$ticket->violation_time_to_resolution."\n";
			
			if($ticket->violation_firstcontact != $checkdata[17])
			   echo $ticket->key." violation_firstcontact mismatch ".$checkdata[17]." ? ".$ticket->violation_firstcontact."\n";
			
			if($ticket->net_minutes_to_firstcontact != $checkdata[18])
			   echo $ticket->key." net_minutes_to_firstcontact mismatch ".$checkdata[18]." ? ".$ticket->net_minutes_to_firstcontact."\n";
			
			if($ticket->net_time_to_firstcontact != $checkdata[19])
			   echo $ticket->key." net_time_to_firstcontact mismatch ".$checkdata[18]." ? ".$ticket->net_time_to_firstcontact."\n";
		}
		else
		    dump($ticket->key." is not found");

	}
	function ProcessDirtyTickets()
	{
		$query=['dirty'=>1];
		$data = $this->db->tickets->find($query);
		$count = 0;
		foreach($data as $ticket)
		{
			$wt=$this->ComputeWaitingTime($ticket);
			$this->SetSlaQuota($ticket);
			$this->UpdateTimeToResolution($ticket);
			$this->UpdateFirstContactDelay($ticket);
			$this->SendTimeToResolutionNotification($ticket);
			$this->SendFirstContactNotification($ticket);
			//$this->Debug($ticket);
			dump("Processing ".$ticket->key);
			$ticket->dirty=0;
			$this->SaveTicket($ticket);
			$count++;
		}
		dump("Processed ".$count." tickets");
	}
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			case 'violation_firstcontact':
				if(isset($issue->fields->customFields[$code]))
				{
					if(strtolower($issue->fields->customFields[$code]->value) == 'yes' || strtolower($issue->fields->customFields[$code]->value) == 'true')
						return 1;
					else
						return 0;
				}
				return 0;
				break;
			case 'violation_time_to_resolution':
				if(isset($issue->fields->customFields[$code]))
				{
					if(strtolower($issue->fields->customFields[$code]->value) == 'yes' || strtolower($issue->fields->customFields[$code]->value) == 'true')
						return 1;
					return 0;
				}
				return 0;
			case 'gross_time_to_resolution':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
				}
				return '';
			case 'solution_provided_date':
				if(isset($issue->fields->customFields[$code]))
				{
					$solution_provided_date= new \DateTime($issue->fields->customFields[$code]);
					$this->SetTimeZone($solution_provided_date);
					return $solution_provided_date->getTimestamp();
				}
				return '';
			case 'test_case_provided_date':
				if(isset($issue->fields->customFields[$code]))
				{
					$test_case_provided_date= new \DateTime($issue->fields->customFields[$code]);
					$this->SetTimeZone($test_case_provided_date);
					return $test_case_provided_date->getTimestamp();
				}
				return '';
			case 'net_time_to_resolution':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
				}
				return '';
			case 'gross_minutes_to_resolution':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
				}
				return '';
			case 'first_contact_date':
				if(isset($issue->fields->customFields[$code]))
				{
					$first_contact_date= new \DateTime($issue->fields->customFields[$code]);
					$this->SetTimeZone($first_contact_date);
					return $first_contact_date->getTimestamp();
				}
				return '';
			case 'reason_for_closure':
				if(isset($issue->fields->customFields[$code]))
				{
					return strtolower($issue->fields->customFields[$code]->value);
				}
				return '';
			case 'premium_support':
				if(isset($issue->fields->customFields[$code]))
				{
					if( strtolower($issue->fields->customFields[$code]->value) == 'yes')
						return 1;
					else
						return 0;
				}
				return 0;
				break;
			case 'account':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];
					
				}
				return '';
			case 'product_name':
				if(isset($issue->fields->customFields[$code]))
				{
					return $issue->fields->customFields[$code];	
				}
				return '';
				break;
			case 'component':
				if(isset($this->issue->fields->customFields[$code]))
				{
					return $this->issue->fields->customFields[$code]->value;
				}
				return '';
			default:
				dd($fieldname.' is not handled in IssueParser');
		}
    }
	function Script()
	{
		
		$fields = new Fields($this,0);
		$fields->Set(['key','updated']);
		//$this->query = 'key in (SIEJIR-5794)'; 
		$tickets =  Jira::FetchTickets($this->query,$fields);
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
			$tickets =  $this->FetchJiraTickets($nquery);
			dump(count($tickets)." tickets found");
			foreach($tickets as $ticket)
			{
				$ticket->dirty = 1;
				$this->SaveTicket($ticket);
			}
		}
		$this->ProcessDirtyTickets();
	}
}