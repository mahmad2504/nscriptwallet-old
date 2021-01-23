<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Product;
use App\Apps\Cveportal\Svm;
use App\Apps\Cveportal\Nvd;
use App\Apps\Cveportal\Cvestatus;
use App\Apps\Cveportal\Cache;

class Jiraa extends Cveportal{
	public $options = 0;
	public $scriptname = "cveportal:jira";
	public $jira_server = 'EPS';
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=10)
	{
		$sync_requested = $this->Read('sync_requested_'.$this->scriptname);
		if($sync_requested)
		{
			$this->Save(['sync_requested_'.$this->scriptname=>0]);
			return true;
		}			
		return parent::TimeToRun($update_every_xmin);
	}
	public function RequestSync()
	{
		$this->Save(['sync_requested_'.$this->scriptname=>1]);
	}
	public function Rebuild()
	{
		dd("Rebuild is not available");
	}
	public function IsUpdated($cve,$product,$version,$ticket)
	{
		$title = $cve->cve;
		$description = 'Description not found';
		$priority = 'NA';
		
		if(isset($cve->nvd->description))
		{
			$description = $cve->nvd->description;
			$priority = $cve->severity;
		}
		
		if($priority != $cve->severity)
		{
			dump($ticket->key." Priority does not match for cve=".$cve->cve);
			return true;
		}
		if($ticket->summary != $title )
		{
			dump($ticket->key." title does not match for cve=".$cve->cve);
			return true;
		}
		if($ticket->description != $description )
		{
			dump($ticket->key." description does not match for cve=".$cve->cve);
			return true;
		}
		if($ticket->product_id != $product->id)
		{
			dump($ticket->key." product id does not match for cve=".$cve->cve);
			return true;
		}	
		if($ticket->cve_id != $cve->cve)
		{
		   dump($ticket->key." cve id does not match for cve=".$cve->cve);
		   return true;
		}
		$vfound = 0;
		foreach($ticket->versions as $version)
		{
			if(strtolower($version)==strtolower($version))
			{
				$vfound = 1;
				break;
			}
		}
		if($vfound==0)
		{
		   dump($ticket->key." version does not match for cve=".$cve->cve);
		   return true;
		}
		return false;
	}
	public function GenerateTickets($cve,$product,$version,$jirakey=null,$option=0)
	{
		$jira_project = $product->jira->project;
		$title = $cve->cve;
		if($option == 1)
			$title = $title." - DEPRECATED";
		if($option == 2)
			$title = $title." - DUPLICATES - ".$cve->jira ;
		$description = 'Description not found';
		$priority = 'NA';
		if(isset($cve->nvd->description))
		{
			$description = $cve->nvd->description;
			$priority = $cve->severity;
		}
		$customefields = [];
		$customefields['product_id']=$product->id;
		$customefields['cve_id']=$cve->cve;
		//$customefields['triage']=$this->default_triage_status;
		
		if($jirakey!=null)
		{
			dump("Updating Jira Ticket for ".$cve->cve." ".$product->id." ".$version." ".$priority);
			Jira::UpdateTask($jirakey,$title,$description,$priority,'Task',$version,$customefields);
		}
		else
		{
			dump("Creating Jira Ticket for ".$cve->cve." ".$product->id." ".$version." ".$priority);
			$ticket =  Jira::CreateTask($jira_project,$title,$description,$priority,'Task',$version,$customefields);
			$ticket->product_id = $product->id;
			$ticket->triage = '';
			$ticket->status = 'Open';
			$ticket->publish = 0;
			return $ticket;
		}
	}
	public function SendErrorNotification($p)
	{
		dump("Version not found in Jira");
		dump("Group = ".$p->group);
		dump("Name = ".$p->name);
		dump("Version = ".$p->version);
		dump("Jira = ".$p->jira->project);
		dump("Affects version = ".$p->jira->affectsversion);
				
		$email =  new Email();
		$to[] = $this->cveportal_admin;
		if(isset($p->notify))
			$to[] = $p->notify;
				
		$msg = "<h3>CVE sync error</h3>";
		$msg .= "<span style='color:red;'>".$p->jira->affectsversion." version not found in jira project ".$p->jira->project."</span><br>";
		$msg .= "<br>"; 
		$msg .= "<h4>Product Details</h4>";
		$msg .= "Product Group = ".$p->group."<br>";
		$msg .= "Product Name = ".$p->name."<br>";
		$msg .= "Version label = ".$p->version."<br>";
				
		$date =  new \DateTime();
		$date = $date->format('Y-m-d');
				
		$d = $this->Read('versionerror');
		if($d ==  null)
		{
			$email->Send(1,'CVE Portal - Sync error report',$msg,$to);
			$p->date =  $date ;
			$p->id = 'versionerror';
			$this->Save($p);
		}
		else if($d->date !=  $date)
		{
			$email->Send(1,'CVE Portal - Sync error report',$msg,$to);
			$p->date =  $date ;
			$p->id = 'versionerror';
			unset($p->_id);
			$this->Save($p);
		}
	}
	public function Script()
	{
		$cvestatus =  new Cvestatus();
		$product = new Product();
		$products = $product->GetProducts();
		$sproducts = [];
		foreach($products as $p)
		{
			if($p->active == 0)
				continue;
			
			if(isset($p->jira))
			{
				$versions = Jira::GetVersions($p->jira->project);
				$version = null;
				foreach($versions as $v)
				{
					if(strtolower($v->name)==strtolower($p->jira->affectsversion))
					{
						$version = $v->name;
						break;
					}
				}
				if($version == null)
				{
					$this->SendErrorNotification($p);
					continue;
				}
				$p->jira->versions = $versions;
				$sproducts[]=$p;
			}
		}
		$created = [];
		$deprecated = [];
		$updated = [];
		$duplicate = [];
		
		foreach($sproducts as $p)
		{
			$query  = ['product.id'=>$p->id];
			$projection = [
			'projection'=>
			["cve"=>1]];
			$cves = $this->db->cves->find($query)->toArray();
			$this->query='project = '.$p->jira->project.' and cf['.explode("_",$this->fields->product_id)[1].'] ~ '.$p->id." and summary !~ DEPRECATED and summary !~ DUPLICATES";
			$tickets = $this->FetchJiraTickets();
			foreach($tickets as $ticket)
			{
				foreach($cves as $cve)
				{
					if($cve->cve == $ticket->cve_id)
					{
						if(isset($cve->jira))
						{
							dump($ticket->key." duplicates ".$cve->jira);
							$ticket->cve = $cve;
							$this->GenerateTickets($cve,$p,$version,$ticket->key,2);
							$duplicate[] = $ticket;
							continue;
						}
						if($this->IsUpdated($cve,$p,$version,$ticket))
						{
							dump("Updating ".$ticket->key);
							$updated[] = $ticket;
							$this->GenerateTickets($cve,$p,$version,$ticket->key);
						}	
						$cve->jira = $ticket->key;
						if($ticket->triage != '')
							$cve->triage = $ticket->triage;
						else
							$cve->triage = $ticket->status;
					
						$cve->publish = $ticket->publish;
						$ticket->cve = $cve;
					}
				}
				if(!isset($ticket->cve))
				{
					dump($ticket->key." no more belongs to any product cve");
					$this->GenerateTickets($cve,$p,$version,$ticket->key,1);
					$ticket->cve = $cve;
					$deprecated[] = $ticket;
					
				}
			}
			/// Ticket Generation 
			foreach($cves as $cve)
			{
				if(!isset($cve->jira))// Jira key is not assigned 
				{
					$ticket = $this->GenerateTickets($cve,$p,$version);
					$cve->jira = $ticket->key;
					if($ticket->triage != '')
						$cve->triage = $ticket->triage;
					else
						$cve->triage = $ticket->status;
					
					$cve->publish = $ticket->publish;
					$ticket->cve = $cve;
					$created [] = $ticket;
				}
				$status = $cvestatus->GetStatus($cve->cve,$p->id);
				if(isset($p->jira))
				{
					if(
						($status->triage != $cve->triage )||
						($status->publish != $cve->publish)||
						($status->source != $cve->jira)
						)
					{
						$status->triage = $cve->triage;
						$status->publish = $cve->publish;
						$status->source = $cve->jira;
						$cvestatus->UpdateStatus($status);
					}
				}
			}
			
			//$this->db->cves->drop();
			//$this->db->cves->insertMany(array_values($this->cves));
		
			
			$msg = '';
			if( count($created) > 0)
			{
				$msg .= "<h3>Following Jira Tickets are created </h3>";
				
				foreach($created as $t)
				{
					$msg .= $t->key." for ".$t->cve->cve."<br>";	
				}
			}
			if( count($deprecated) > 0)
			{
				$msg .= "<h3>Following Jira Tickets are deprecated and should be removed </h3>";
				
				foreach($deprecated as $t)
				{
					$msg .= $t->key." for ".$t->cve->cve."<br>";	
				}
			}
			if( count($updated) > 0)
			{
				$msg .= "<h3>Following Jira Tickets are updated  </h3>";
				
				foreach($updated as $t)
				{
					$msg .= $t->key." for ".$t->cve->cve."<br>";	
				}
			}
			if( count($duplicate) > 0)
			{
				$msg .= "<h3>Following Jira Tickets are duplicate  </h3>";
				
				foreach($duplicate as $t)
				{
					$msg .= $t->key." for ".$t->cve->cve."<br>";	
				}
			}
		
			if($msg != '')
			{
				$email =  new Email();
				$to[] = $this->admin;
				if(isset($p->notify))
					$to[] = $p->notify;
				$nmsg = "<h3>CVE sync report</h3>";
				$nmsg .= "<h4>Product Details</h4>";
				$nmsg .= "Product Group = ".$p->group."<br>";
				$nmsg .= "Product Name = ".$p->name."<br>";
				$nmsg .= "Version label = ".$p->version."<br>";
				$nmsg .= "<br>";
				$nmsg .= $msg;
				$email->Send(1,'CVE Portal - Sync report',$nmsg,$to);
			} 
			$cache = new Cache();
			$cache->Clean();
		}
	}
}