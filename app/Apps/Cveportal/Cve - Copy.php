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
		$this->InitDb();
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
		if($cvss['version'] == "2.0")
		{
			if($cvss["baseScore"] <= 3.9)
				$severity = 'LOW';
			
			else if($cvss["baseScore"] <= 6.9)
				$severity = 'MEDIUM';
			
			else if($cvss["baseScore"] <= 10.0)
				$severity =  'HIGH';
			else
				$severity =  'CRITICAL';
		}
		else
		{
			if($cvss["baseScore"] == 0)
				$severity = 'NONE';
			else if($cvss["baseScore"] <= 3.9)
				$severity = 'LOW';
			
			else if($cvss["baseScore"] <= 6.9)
				$severity = 'MEDIUM';
			
			else if($cvss["baseScore"] <= 8.9)
				$severity =  'HIGH';
			
			else if($cvss["baseScore"] <= 10.0)
				$severity =  'CRITICAL';
			
			else
				$severity =  'CRITICAL';
			
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
							if(!isset($this->cves[$cve]->nvd->cvss['baseSeverity']))
							{
								$this->cves[$cve]->nvd->cvss['baseSeverity'] = $this->ComputeSeverity($this->cves[$cve]->nvd->cvss);
							}
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
			}
		}
		$this->db->cves->drop();
		$this->db->cves->insertMany(array_values($this->cves));
	}
}