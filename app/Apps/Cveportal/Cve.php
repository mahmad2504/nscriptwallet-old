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

class Cve extends App{
	public $timezone='Asia/Karachi';
	public $options = 0;
	public $scriptname = "cve";
	public $count=0;
	public $query='';
	public $jira_fields = ['key','status','statuscategory','summary','versions','description']; 
    public $jira_customfields = ['cve_id'=>'customfield_10444','product_id'=>'External ID'];  
    public $cveportal_admin = 'mumtaz_ahmad@mentor.com';	
	//public $jira_customfields = ['customer'=>'Customer'];  	
	private $cves = [];
	public $jira_server = 'EPS';
	public $jira_project = [];
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
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			case 'product_id':
			case 'cve_id':
				if(isset($issue->fields->customFields[$code]))
					return $issue->fields->customFields[$code];
				return null;
				break;
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
	}
	public function Rebuild()
	{
		//$this->db->monitoring_lists->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	private function ProcessCveRecord($record,$id=null)
	{
		$cve = new \StdClass();
		$cve->cve = $record->cve;
		if(!isset($record->nvd))
			return null;
		if($record->nvd==null)
			return null;
		
		$cve->description = $record->nvd->description;
		$cve->modified = $record->nvd->lastModifiedDate;
		$cve->published = $record->nvd->publishedDate;
		if($record->nvd->cvss == null)
			return null;
		
		$cve->cvss = $record->nvd->cvss;
		/*if(isset($record->nvd->cvssv3))
			$cve->cvssv3 = $record->nvd->cvssv3;
		
		if(isset($record->nvd->cvssv2))
			$cve->cvssv2 = $record->nvd->cvssv2;*/
		
		$cve->product = $record->product;
		$remove = [];
		$i=0;
		$valid = 0;
		$product_id = "-1";
		$p = new Products();
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
	function Get($ids)
	{		
		$options = [
			'sort' => ['nvd.lastModifiedDate' => -1],
			'projection'=>
					["_id"=>0,
					"nvd.description"=>1,
					"nvd.lastModifiedDate"=>1,
					"nvd.publishedDate"=>1,
					"nvd.cvss"=>1,
					"product.id"=>1,
					"product.component.name"=>1,
					"product.component.version"=>1,
					"cve"=>1]
		];
		$query  = ['product.id'=>['$in'=>$ids]];
		
		$cves = $this->db->cves->find($query,$options)->toArray();
		
		$cvedata = [];
		foreach($cves as $record)
		{
			$r = $this->ProcessCveRecord($record,$ids);
			if($r != null)
				$cvedata[] = $r;
		}
		return $cvedata;
	}
	function ComputeSeverity($cvss)
	{
		//echo $cvss["baseScore"]."\r\n";
		/*if($cvss['version'] == "2.0")
		{
			if($cvss["baseScore"] <= 3.9)
				$severity = 'LOW';
			
			else if($cvss["baseScore"] <= 6.9)
				$severity = 'MINOR';
			
			else if($cvss["baseScore"] <= 10.0)
				$severity =  'Major';
			else
				$severity =  'CRITICAL';
		}
		else*/
		{
			if($cvss["baseScore"] == 0)
				$severity = 'NA';
			else if($cvss["baseScore"] <= 3.9)
				$severity = 'Trivial';
			
			else if($cvss["baseScore"] <= 6.9)
				$severity = 'Minor';
			
			else if($cvss["baseScore"] <= 8.9)
				$severity =  'Major';
			else
				$severity =  'Critical';
			
		}
		//echo $severity."\r\n";
		return $severity;
	}
	function BuildCVEs($product,$components)
	{
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
		
		foreach($cpes as $cpe)
		{
			$component_id = $cpe->id;
			$component_name = $cpe->component_name;
			$component_version = $cpe->version;
			
			foreach($cpe->cve as $cve)
			{

				if(!array_key_exists($cve,$this->cves))
				{
					$this->cves[$cve] = new \StdClass();
					$this->cves[$cve]->cve = $cve;
					$this->cves[$cve]->product = [];
					$this->cves[$cve]->severity = 'NA';
					
					$cve_nvd_data = $nvd->GetCve($cve);
					if($cve_nvd_data != null)
					{
						$this->cves[$cve]->nvd = new \StdClass();
						$lastModifiedDate = $cve_nvd_data['lastModifiedDate']->toDateTime()->format(DATE_ATOM);
						$this->cves[$cve]->nvd->lastModifiedDate = substr($lastModifiedDate,0,10);
						
						$publishedDate = $cve_nvd_data['publishedDate']->toDateTime()->format(DATE_ATOM);
						$this->cves[$cve]->nvd->publishedDate = substr($publishedDate,0,10);
						
						$this->cves[$cve]->nvd->description = $cve_nvd_data['cve']['description']['description_data'][0]['value'];
						
						
						$this->cves[$cve]->nvd->cvss = null;
						if(isset($cve_nvd_data['impact']['baseMetricV3']))
						{
							$this->cves[$cve]->nvd->cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV3']['cvssV3']);
							//$this->cves[$cve]->nvd->cvssv3 = iterator_to_array($cve_nvd_data['impact']['baseMetricV3']['cvssV3']);
						}
						else if(isset($cve_nvd_data['impact']['baseMetricV2']))
						{
							$this->cves[$cve]->nvd->cvss  =  iterator_to_array($cve_nvd_data['impact']['baseMetricV2']['cvssV2']);
							//$this->cves[$cve]->nvd->cvssv2 = iterator_to_array($cve_nvd_data['impact']['baseMetricV2']['cvssV2']);
						}
						if($this->cves[$cve]->nvd->cvss != null)
						{
							//if(!isset($this->cves[$cve]->nvd->cvss['baseSeverity']))
							//{
							//	$this->cves[$cve]->nvd->cvss['baseSeverity'] = $this->ComputeSeverity($this->cves[$cve]->nvd->cvss);
							//}
							$this->cves[$cve]->severity = $this->ComputeSeverity($this->cves[$cve]->nvd->cvss);
						}
						//echo $cve_nvd_data['cve']['CVE_data_meta']['ID'];
						//dump($this->cves[$cve]->nvd);
					}
					else
					{
						$this->cves[$cve]->nvd = null;
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
		if($jirakey!=null)
		{
			dump("Updating Jira Ticket for ".$cve->cve." ".$product->id." ".$version." ".$priority);
			Jira::UpdateTask($jirakey,$title,$description,$priority,'Task',$version,$customefields);
		}
		else
		{
			dump("Creating Jira Ticket for ".$cve->cve." ".$product->id." ".$version." ".$priority);
			return Jira::CreateTask($jira_project,$title,$description,$priority,'Task',$version,$customefields);
		}
	}
	public function Script()
	{
		
		$product = new Product();
		$products = $product->GetProducts();
		
		foreach($products as $p)
		{
			$components = $product->GetComponents($p->id);
			
			
			$this->BuildCVEs($p,$components);
			
			foreach($this->cves as $cve)
			{
				foreach($cve->product as $prod)
					$prod->component = array_values($prod->component);
				$cve->product = array_values($cve->product);
				//$this->GenerateTickets($cve);
				
			}
		}
		$this->db->cves->drop();
		$this->db->cves->insertMany(array_values($this->cves));
		// Create Jira Tickets
		$created = [];
		$deprecated = [];
		$updated = [];
		$duplicate = [];
		foreach($products as $p)
		{
			if($p->active == 0)
				continue;
			
			if(!isset($p->jira))
				continue;
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
					$this->Save($p);
				}
				continue;
			}
			$query  = ['product.id'=>$p->id];
			$projection = [
			'projection'=>
			["cve"=>1]];
			$cves = $this->db->cves->find($query)->toArray();
			
			$this->query='cf['.explode("_",$this->fields->product_id)[1].'] ~ '.$p->id." and summary !~ DEPRECATED and summary !~ DUPLICATES";
			
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
					$t = $this->GenerateTickets($cve,$p,$version);
					$t->cve = $cve;
					$created [] = $t;
				}
			}
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
				$to[] = $this->cveportal_admin;
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
		}
	}
}