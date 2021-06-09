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

class Cve extends Cveportal{
	public $options = 0;
	public $scriptname = "cveportal_cve";
	public $not_applicable_string = 'Not Applicable';
	public function __construct($options)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		parent::__construct($options);
    }
	public function TimeToRun($update_every_xmin=1440)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	
	public function Rebuild()
	{
		$this->options['email']=0;// no emails when rebuild
	}
	
	function ComputeSeverity($cvss_score,$cvss_version)
	{
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
		return $severity;
	}
	public function GetCVETriageStatus($cve,$productid)
	{
		$cvestatus = new CVEStatus($this->options);
		$status = $cvestatus->GetStatus($cve,$productid);
		$status = $status->Jsonserialize();
		return $status;
	}
	function Get($ids,$limit=0,$skip=0,$cur_product_id=null,$admin_ids=[],$valid_only=0,$publishable_only=0)
	{
		$p = new Product($this->options);
		
		$options = [
			'sort' => ['priority' => 1],
			
		];
		
		$query = [];
		if($ids != null)
			$query['products']=['$in'=>$ids];
		
		$query['priority']= ['$lt'=>4];
		
		if($valid_only==1)
			$query['not_applicable'] = 0;
		
		if($publishable_only==1)
			$query['publishable'] = 1;
	
		
		$total = $this->db->cverecords->count($query,$options);
		if($limit > 0)
			$options['limit']=$limit;
		if($skip > 0)
			$options['skip']=$skip;	
		$cves = $this->db->cverecords->find($query,$options)->toArray();
		$cvedata = [];
		foreach($cves as $cve)
		{
			$invalid_cve = 1;
			foreach($cve->product as $pid=>$pdata)
			{
				$details = $p->GetProducts(['id'=>$pid],['group'=>1,'name'=>1,'version'=>1,'publish'=>1]);
				if(count($details)!=1)
					dd('Product should be in database');
				
				$pdata->group = $details[0]->group;
				$pdata->name = $details[0]->name;
				$pdata->version = $details[0]->version;
				$pdata->current = 0;
				if($pdata->id == $cur_product_id)
					$pdata->current = 1;
				
				$pdata->status = $this->GetCVETriageStatus($cve->cve,$pid);
				$pdata->status->readonly=1;
				
				if(in_array($pid,$admin_ids))
					$pdata->status->readonly=0;
				if($pdata->current)
					$cve->status = $pdata->status;
				
				if($pdata->status->triage != "Not Applicable")
					$invalid_cve = 0;
			}
			$cve->invalid_cve=$invalid_cve;
			$cvedata[] = $cve;
			
		}
		$cvedata['total']=$total;
		return $cvedata;
	} 
	function BuildCVEs($product,$components)
	{
		$nvd = new Nvd($this->options);
		$svm =  new Svm($this->options);
		
		
		$product_id = $product->id;
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
					$cverecord->product[$product_id]->created=1;
				}
				else
					$cverecord->product[$product_id]->created=0;
				
				if(!isset($cverecord->product->$product_id->component->$component_id))
				{
					$cverecord->product[$product_id]->component[$component_id]=new \StdClass();
					$cverecord->product[$product_id]->component[$component_id]->id = $component_id;
					$cverecord->product[$product_id]->component[$component_id]->name = $component_name;
					$cverecord->product[$product_id]->component[$component_id]->version = $component_version;
					$cverecord->product[$product_id]->component[$component_id]->created = 1;
				}
				else
					$cverecord->product[$product_id]->component[$component_id]->created = 0;
				
				$cverecord->product[$product_id]->component[$component_id]->valid = 1;
				$cverecord->product[$product_id]->valid=1;
				
				$this->UpdateStatus($cverecord);
				
				//$this->db->cverecords->updateOne(['cve'=>$cve],['$set'=>$cverecord],['upsert'=>true]);
			}
		}
	}
	public function UpdateStatus($cve)
	{
		$status =  new Cvestatus($this->options);
		if(is_string($cve))
			$cverecord = $this->db->cverecords->findOne( ['cve'=>$cve]);
		else
			$cverecord = $cve;
		if($cverecord == null)
			dd($cve." data not found");
		
		$cverecord->publishable=0;
		$cverecord->not_applicable=1;
		
		foreach($cverecord->product as $pid=>$data)
		{
			$st = $status->GetStatus($cverecord->cve,$pid);
			if(($st->publish == 1)||($st->publish == "1"))
				$cverecord->publishable=1;
			if($st->triage != 'Not Applicable')
				$cverecord->not_applicable=0;
		}
		
		
		$this->db->cverecords->updateOne(['cve'=>$cverecord->cve],['$set'=>$cverecord],['upsert'=>true]);
		
	}
	public function GetCve($cve)
	{
		return $this->db->cverecords->findOne( ['cve'=>$cve]);
	}
	public function Script()
	{
		$svm = new Svm($this->options);
		$product = new Product($this->options);
		$products = $product->GetProducts();
		$locked = [];	
		foreach($products as $p)
		{
			$components = $svm->Components($p->id);
			if($p->lock)
				$locked[$p->id]=$p->id;
			$this->BuildCVEs($p,$components);
		}
		$cves = $this->db->cverecords->find();
		foreach($cves as $cve)
		{
			$punset = [];
			$cunset = [];
			$cve->products = [];
			foreach($cve->product as $pid=>$pdata)
			{	
				$cve->products[$pid]=$pid;
				if($pdata->valid == 0)
				{
					//dump("  X1 ".$pid."   C[".$pdata->created."]  V[".$pdata->valid."]   L[".isset($locked[$pid])."]");
					$punset[$pid] = $pid;		
				}
				else
				{
					if(($pdata->created)&&isset($locked[$pid]))
					{
						//dump("  X2 ".$pid."   C[".$pdata->created."]  V[".$pdata->valid."]   L[".isset($locked[$pid])."]");
						$punset[$pid] = $pid;
					}
					else
					{	
						//dump("   ".$pid."   C[".$pdata->created."]  V[".$pdata->valid."]   L[".isset($locked[$pid])."]");
						$pdata->created=0;
						$pdata->valid=0;
						foreach($pdata->component as $cid=>$cdata)
						{
							if($cdata->valid == 0)
							{
								//dump("     X3".$cid."   C[".$cdata->created."]  V[".$cdata->valid."]");
								$cunset[$cid]=$cid;
							}
							else
							{
								//dump("      ".$cid."   C[".$cdata->created."]  V[".$cdata->valid."]");
								$cdata->created=0;
								$cdata->valid=0;
							}
						}
					}
				}
			}
			foreach($cunset as $cid)
				unset($cve->product->component->$cid);
			foreach($punset as $pid)
			{
				unset($cve->product->$pid);
				unset($cve->products[$pid]);
			}
			//if(is_array($cve->products))
			//	$cve->products =  array_values($cve->products);
			//else
			$cve->products =  array_values($cve->products);
			if( count($cve->products) == 0)
				$this->db->cverecords->deleteOne(['cve'=>$cve->cve]);
			else
				$this->db->cverecords->updateOne(['cve'=>$cve->cve],['$set'=>$cve],['upsert'=>true]);
		}
		dump("Creating index");
		$this->db->cverecords->createIndex(["cve"=>'text']);
			
	}
	function IsItPublished($cve)
	{
		if(isset($cve->status->publish))
		{
			if( ($cve->status->publish == 1)||($cve->status->publish == "1"))
				return 1;
			return 0;
		}
		foreach($cve->products as $pid)
		{
			$product = $cve->product->$pid;
			if( ($product->status->publish == 1)||($product->status->publish == "1"))
				return 1;
		}
		return 0;
	}
	function IsItInvalid($cve)
	{
		if(isset($cve->status->triage))
		{
			if( $cve->status->triage != $this->not_applicable_string)
				return 0;
			return 1;
		}
		foreach($cve->products as $pid)
		{
			$product = $cve->product->$pid;
			if($product->status->triage != $this->not_applicable_string)			
				return 0;
		}
		return 1;
	}
}