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

class Cve extends Cveportal{
	public $options = 0;
	public $scriptname = "cveportal:cve";
	public $count=0;
	public $query='';
    
	//public $jira_customfields = ['customer'=>'Customer'];  	
	private $cves = [];
	public $jira_server = 'EPS';
	public $jira_project = [];
	public function GetGoogleUrl()
	{
		return $this->url;
	}
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		$this->cves = [];
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=10)
	{
		return true;
		//return parent::TimeToRun($update_every_xmin);
	}
	
	public function Rebuild()
	{
		//$this->db->monitoring_lists->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	
	function ComputeSeverity($cvss_score,$cvss_version)
	{
		//echo $cvss["baseScore"]."\r\n";
		
		if($cvss_version == 2)
		{
			if($cvss_score <= 3.9)
				$severity = 'Low';
			
			else if($cvss_score <= 6.9)
				$severity = 'Medium';
			
			else if($cvss_score <= 10.0)
				$severity =  'Major';
			else
				$severity =  'Critical';
		}
		else
		{
			if($cvss_score == 0)
				$severity = 'NA';
			else if($cvss_score <= 3.9)
				$severity = 'Low';
			
			else if($cvss_score <= 6.9)
				$severity = 'Medium';
			
			else if($cvss_score <= 8.9)
				$severity =  'Major';
			else
				$severity =  'Critical';
			
		}
		//dump($cvss_version."   ".$cvss_score."  ".$severity);
		//echo $severity."\r\n";
		return $severity;
	}

	function BuildCVEs($product,$components)
	{
		$nvd = new Nvd();
		//$nvd = new NvdSearch();
		//$nvd->Init();
		$svm =  new Svm();
		$nvd =  new Nvd();
		$product_id = $product->id;
		$projection = [
			'projection'=>
			["_id"=>0,
			"component_name"=>1,
			"version"=>1,
			"id"=>1,
			"cve"=>1]
		];
		$query = ['id'=>$product_id];
		$mlist = $this->db->monitoring_lists->findOne($query);
		if($mlist == null)
		{
			dd($product_id." is empty");
		}
		$cpes = $svm->GetCpe($mlist->components);
		dump($product_id);
		foreach($cpes as $cpe)
		{
			$component_id = $cpe->id;
			$component_name = $cpe->component_name;
			$component_version = $cpe->version;
			foreach($cpe->cve as $cve)
			{
				$notification_ids = $cpe->notification_ids->$cve->jsonSerialize();
				$last_notification_id = end($notification_ids);
				
				// GetCve
				$cverecord = $this->db->cverecords->findOne( ['cve'=>$cve]);
				if($cverecord == null)
				{
					$cverecord = new \StdClass();
					$cverecord->cve = $cve;
					$cverecord->product = [];
					$cverecord->solution = $cpe->solution->$cve->$last_notification_id;
					$cverecord->basescore = $cpe->basescore->$cve->$last_notification_id;
					$cverecord->vector = $cpe->vector->$cve->$last_notification_id;
					$cverecord->priority = $cpe->priority->$cve->$last_notification_id;
					$cverecord->cvss_version = $cpe->cvss_version->$cve->$last_notification_id;
					$cverecord->notification_id = $last_notification_id;
					$cverecord->publish_date = $cpe->publish_date->$cve->$last_notification_id;
					$cverecord->last_update = $cpe->last_update->$cve->$last_notification_id;
					$cverecord->severity = $this->ComputeSeverity($cverecord->basescore,$cverecord->cvss_version);
					$cverecord->title = $cpe->title->$cve->$last_notification_id;
					$cve_nvd_data = $nvd->GetCve($cve);
					if($cve_nvd_data != null)
					{
						$cverecord->title = $cve_nvd_data['cve']['description']['description_data'][0]['value'];
						
					}
				}
				if(!isset($cverecord->product->$product_id))
				{
					$cverecord->product[$product_id] = new \StdClass();
					$cverecord->product[$product_id]->id = $product_id;
					$cverecord->product[$product_id]->component[$component_id]=new \StdClass();
					$cverecord->product[$product_id]->component[$component_id]->id = $component_id;
					$cverecord->product[$product_id]->component[$component_id]->name = $component_name;
					$cverecord->product[$product_id]->component[$component_id]->version = $component_version;
				}
				else
				{
					if(!isset($cverecord->product->$product_id->component->$component_id))
					//if(!array_key_exists($component_id,$this->cves[$cve]->product[$product_id]->component))
					{
						$cverecord->product[$product_id]->component[$component_id]=new \StdClass();
						$cverecord->product[$product_id]->component[$component_id]->id = $component_id;
						$cverecord->product[$product_id]->component[$component_id]->name = $component_name;
						$cverecord->product[$product_id]->component[$component_id]->version = $component_version;
					}
				}
				$this->db->cverecords->updateOne(['cve'=>$cve],['$set'=>$cverecord],['upsert'=>true]);
				
			
				if(!array_key_exists($cve,$this->cves))
				{
					$this->cves[$cve] = new \StdClass();
					$this->cves[$cve]->cve = $cve;
					
					$this->cves[$cve]->product = [];
		
					$this->cves[$cve]->solution = $cpe->solution->$cve->$last_notification_id;
					
					$this->cves[$cve]->basescore = $cpe->basescore->$cve->$last_notification_id;
					$this->cves[$cve]->vector = $cpe->vector->$cve->$last_notification_id;
					$this->cves[$cve]->priority = $cpe->priority->$cve->$last_notification_id;		
					$this->cves[$cve]->cvss_version = $cpe->cvss_version->$cve->$last_notification_id;		
					$this->cves[$cve]->notification_id = $last_notification_id;
					$this->cves[$cve]->publish_date = $cpe->publish_date->$cve->$last_notification_id;
					$this->cves[$cve]->last_update = $cpe->last_update->$cve->$last_notification_id;	
					$this->cves[$cve]->severity = $this->ComputeSeverity($this->cves[$cve]->basescore,$this->cves[$cve]->cvss_version);
					$this->cves[$cve]->title = $cpe->title->$cve->$last_notification_id;	
					
					$cve_nvd_data = $nvd->GetCve($cve);
					
					if($cve_nvd_data != null)
					{
						
						//$lastModifiedDate = $cve_nvd_data['lastModifiedDate']->toDateTime()->format(DATE_ATOM);
						//$this->cves[$cve]->nvd->lastModifiedDate = substr($lastModifiedDate,0,10);
						
						//$publishedDate = $cve_nvd_data['publishedDate']->toDateTime()->format(DATE_ATOM);
						//$this->cves[$cve]->nvd->publishedDate = substr($publishedDate,0,10);
						$this->cves[$cve]->title = $cve_nvd_data['cve']['description']['description_data'][0]['value'];
						
						if($this->cves[$cve]->severity == 'NA')
						{
							$cvss_version=0;
							if(isset($cve_nvd_data['impact']['baseMetricV3']))
							{
								$cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV3']['cvssV3']);
								$cvss_version=3;
							}
							else if(isset($cve_nvd_data['impact']['baseMetricV2']))
							{
								$cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV2']['cvssV2']);
								$cvss_version=2;
							}
							if($cvss != null)
							{
								$this->cves[$cve]->severity = $this->ComputeSeverity($cvss['baseScore'],$cvss_version);
								$this->cves[$cve]->vector = $cvss['vectorString'];
								$this->cves[$cve]->basescore = $cvss['baseScore'];
								$this->cves[$cve]->cvss_version = $cvss_version;
							}
						}
						//echo $cve_nvd_data['cve']['CVE_data_meta']['ID'];
						//dump($this->cves[$cve]->nvd);
					}
				}
				
				if(!array_key_exists($product_id,$this->cves[$cve]->product))
				{
					$this->cves[$cve]->product[$product_id] = new \StdClass();
					$this->cves[$cve]->product[$product_id]->id = $product_id;
					$this->cves[$cve]->product[$product_id]->component[$component_id]=new \StdClass();
					$this->cves[$cve]->product[$product_id]->component[$component_id]->id = $component_id;
					$this->cves[$cve]->product[$product_id]->component[$component_id]->name = $component_name;
					$this->cves[$cve]->product[$product_id]->component[$component_id]->version = $component_version;
				}
				else
				{
					if(!array_key_exists($component_id,$this->cves[$cve]->product[$product_id]->component))
					{
						$this->cves[$cve]->product[$product_id]->component[$component_id]=new \StdClass();
						$this->cves[$cve]->product[$product_id]->component[$component_id]->id = $component_id;
						$this->cves[$cve]->product[$product_id]->component[$component_id]->name = $component_name;
						$this->cves[$cve]->product[$product_id]->component[$component_id]->version = $component_version;
					}
				}
			}
		}
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
			$title = $title." - DUPLICATES - ".$cve->jira->key ;
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
		$customefields['triage']=$this->default_triage_status;
		
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
			$ticket->triage = $this->default_triage_status;
			return $ticket;
		}
	}
	public function GetCVETriageStatus($cve,$productid)
	{
		$cvestatus = new CVEStatus();
		$status = $cvestatus->GetStatus($cve,$productid);
		$status = $status->Jsonserialize();
		return $status;
	}
	private function ProcessCveRecord($cve,$id=null)
	{
		$remove = [];
		$i=0;
		$valid = 0;
		$product_id = "-1";
		$p = new Product();
		if($id != null)
			if(count($id)==1)
				$product_id = $id[0];
		$cve->status = 'Not Applicable';
		foreach($cve->product as $product)
		{
			$details = $p->GetProduct($product->id);
			$product->group = $details->group;
			$product->name = $details->name;
			$product->version = $details->version;
			$product->status = $this->GetCVETriageStatus($cve->cve,$product->id);
			if($product_id == $product->id)
			{
				$cve->status = $product->status;
			}
		}
		return $cve;
	}
	
	function Get($ids,$cve=null,$severity=null,$limit=0,$skip=0)
	{
		$options = [
			'sort' => ['priority' => 1],
			/*'projection'=>
					["_id"=>0,
					"nvd.description"=>1,
					"nvd.lastModifiedDate"=>1,
					"nvd.publishedDate"=>1,
					//"nvd.cvss"=>1,
					"product.id"=>1,
					"product.component.name"=>1,
					"product.component.version"=>1,
					"severity"=>1,
					"solution"=>1,
					"cve"=>1]*/
		];
		
		$query = [];
		if($ids != null)
			$query['product.id']=['$in'=>$ids];
		if($cve != null)
			$query['cve']=['$regex'=> new \MongoDB\BSON\Regex( preg_quote($cve),"i")];
		if($severity != null)
			$query['severity']=['$regex'=> new \MongoDB\BSON\Regex( preg_quote($severity),"i")];
		
		$query['priority']= ['$lt'=>4];
		
		$total = $this->db->cves->count($query,$options);
		
		if($limit > 0)
			$options['limit']=$limit;
		
		if($skip > 0)
			$options['skip']=$skip;
				
		$cves = $this->db->cves->find($query,$options)->toArray();
		
		$cvedata = [];
		foreach($cves as $record)
		{
			if($record->priority >= 4)
				continue;
			
			$r = $this->ProcessCveRecord($record,$ids);
			if($r != null)
				$cvedata[] = $r;
		}
		$cvedata['total']=$total;
		return $cvedata;
	}
	function IsItPublished($cve)
	{
		if(isset($cve->status->publish))
		{
			if( ($cve->status->publish == 1)||($cve->status->publish == "1"))
				return 1;
			return 0;
		}
		if(($cve->published == 1)||($cve->published == "1"))
			return 1;
		return 0;
	}
	function GetPublished($ids,$cve=null,$severity=null,$limit=0,$skip=0)
	{
		$cves = $this->Get($ids,$cve,$severity,$limit,$skip);
		
		$total = $cves['total'];
		unset($cves['total']);
		$cve_delete_indexes = [];
		$cve_index = 0;
		
		foreach($cves as $cve)
		{
			$index = 0;
			$delete_indexes = [];
			$published=0;
			$invalid_cve = 1;
			foreach($cve->product as $product)
			{
				if($product->status->publish == 1)
					$published = 1;
				
				
				if($product->status->triage != "Not Applicable")
					$invalid_cve = 0;
				//	$delete_indexes[]=$index;
				//else
				//{
				//	if( in_array($product->id,$ids))
				//		$valid=1;
				//}
				//$index++;
			}
			$cve->published=$published;
			$cve->invalid_cve=$invalid_cve;
			//foreach($delete_indexes  as $index)
			//{
			//	unset($cve->product[$index]);
			//}
			//if(($valid==0)||(count($cve->product)==0))
			//	$cve_delete_indexes[] = $cve_index; 
			//$cve_index++;
		}
		//foreach($cve_delete_indexes as $cve_index)
		//{
			//unset($cves[$cve_index]);
			//$cves[$cve_index]->show=0;
	//	}
		
		$cves['total']=$total;
		return $cves;
		//return array_values($cves);
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
			}
			$components = $product->GetComponents($p->id);
			$this->BuildCVEs($p,$components);
			foreach($this->cves as $cve)
			{
				//if($cve->severity == 'NA')
				//	dd($cve);
				
				foreach($cve->product as $prod)
					$prod->component = array_values($prod->component);
				$cve->product = array_values($cve->product);
				//$this->GenerateTickets($cve);
			}
			$sproducts[]=$p; 
		}
		//dd($sproducts);
		$this->db->cves->drop();
		$this->db->cves->insertMany(array_values($this->cves));
		$cache = new Cache();
		$cache->Clean();
		return;
	}
}