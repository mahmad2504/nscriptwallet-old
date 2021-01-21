<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use \MongoDB\BSON\Regex;

class Product extends Cveportal{
	public $scriptname = 'cveportal:product';
	public $jira_server = 'EPS';
	public function __construct($options=null,$data=null)
    {
		if($data == null)
		{
			$this->data = null;
		}
		else
			$this->data = $data;
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=1)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	/*function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			default:
				dd('"'.$fieldname.'" not handled in IssueParsers');
		}
	}*/
	public function Rebuild()
	{
		//$this->db->products->drop();
		//$this->options['email']=0;// no emails when rebuild
	}
	function DbGet($id)
	{
		$query=['id'=>$id];
		$obj = $this->db->products->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function DbGetAll()
	{
		$query=[];
		$obj = $this->db->products->find($query);
		if($obj == null)
			return null;
		return $obj;
	}
	function GetProducts($admin=null,$active="1")
	{
		$query = [];
		if($active != null)
			$query['active'] = $active;
		
		if($admin!=null)
			$query['admin'] = new Regex(preg_quote("".$admin), 'i');
		
		$obj = $this->db->products->find($query);
		if($obj == null)
			return null;
		
		return $obj->toArray();
		
	}
	function GetComponents($id)
	{
		$query=['id'=>$id];
		$obj = $this->db->monitoring_lists->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj->components;
	}
	function DbPut($product)
	{
		$query=['id'=>$product->id];
		$options=['upsert'=>true];
		$this->db->products->updateOne($query,['$set'=>$product],$options);
	}
	function MonitoringLists()
	{
		$query=[];
		$obj = $this->db->monitoring_lists->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj->monitoring_lists->jsonSerialize();
	}
	function GetGroupNames($admin=null,$active="1")
	{
		$products = $this->GetProducts($admin,$active);
		if($products == null)
			return [];
		$groups = [];
		foreach($products as $product)
		{
			if(isset($product->active))
				if($product->active == 0)
					continue;
				
			$groups[$product->group]=$product->group;
		}
		return 	array_values($groups);
	}
	function GetProductNames($groupname,$active="1")
	{
		$query=['group'=>$groupname];
		if($active != null)
			$query['active'] = $active;
		
		$products = $this->db->products->find($query);
		if($products == null)
			return [];
		$names = [];
		foreach($products as $product)
		{
			$names[$product->name]=$product->name;
		}
		return array_values($names);
	}
	function GetVersionNames($group_name,$productname,$active="1")
	{
		$query=['group'=>$group_name,'name'=>$productname];
		if($active != null)
			$query['active'] = $active;
		
		$products = $this->db->products->find($query);
		if($products == null)
			return [];
		$versions  = [];
		foreach($products as $product)
		{
			$versions[$product->version]=$product->version;
		}
		return array_values($versions);
	}
	public function GetIds($groupname=null,$productname=null,$versionname=null,$admin=null,$active="1")
	{
		$products = $this->GetCveProducts($groupname,$productname,$versionname,$admin,$active);
		$ids = [];
		foreach($products as $product)
		{
			$ids[$product->id] = $product->id;	
		}
		return array_values($ids);
	}
	public function GetCveProducts($groupname=null,$productname=null,$versionname=null,$admin=null,$active="1")
	{
		$query = [];
		if($groupname!=null)
			$query['group'] = new Regex(preg_quote($groupname), 'i');
		if($productname!=null)
			$query['name'] = new Regex(preg_quote($productname), 'i');
		if($versionname!=null)
			$query['version'] = $versionname;//new Regex(preg_quote("".$versionname), 'i');	
		
		if($active != null)
			$query['active'] = $active;
		
		if($admin!=null)
			$query['admin'] = new Regex(preg_quote("".$admin), 'i');
		$options = [
			'projection'=>
					["_id"=>0,
					]
		];
		$list = $this->db->products->find($query,$options)->toArray();
		return $list;
	}
	public function GetProduct($id)
	{
		$query['id'] = new Regex(preg_quote($id), 'i');
		$options = [
			'projection'=>
					["_id"=>0,
					]
		];
		return $this->db->products->findOne($query,$options);	
	}
	public function DumpInfo()
	{
		$group_names = $this->GetGroupNames(null,null);
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			dump($group_name);
			$productnames = $this->GetProductNames($group_name,null);
			foreach($productnames as $productname)
			{
				$ps = $this->GetCveProducts($group_name,$productname,null,null,null);
				if(count($ps) > 0)
				{
					foreach($ps as $p)
					dump("    ".$p->name." ".$p->version."[id=".$p->id."][active=".$p->active."]");
				}
			}
			$product_names[] = $productnames;
		}
	}
	public function Script()
	{
		$data = $this->data;
		if(!isset($data->monitoring_lists))
		{
			dd("monitoring_lists not declared");
		}
		$monitoring_lists= [];
		foreach($data->monitoring_lists as $id)
		{
			if(isset($monitoring_lists[$id]))
				dd('Monitoring list '.$id." is duplicate");
			$monitoring_lists[$id]=$id;
		}
		if(!isset($data->groups))
		{
			dd("groups not declared");
		}
		$products = [];
		foreach($data->groups as $groupname=>$group_list)
		{
			foreach($group_list as $productname=>$product)
			{
				foreach($product as $versionname=>$version)
				{
					if(!isset($monitoring_lists[$version->id]))
						dd($productname.'['.$versionname.'] has invalid monitoring list id'.$version->id);
					if(!isset($version->parents))
					{
						$version->parents = [];
					}
					foreach($version->parents as $parent)
					{
						if(!isset($monitoring_lists[$parent]))
							dd($productname.' has invalid parent '.$parent);
					
					}
					$version->name = $productname;
					$version->version = $versionname;
					$version->group = $groupname;
					$products[] = $version;
				}
			}
		}
		$this->db->products->drop();
		foreach($products as $product)
		{
			$this->DbPut($product);
		}
		$monitoring_lists = array_values($monitoring_lists);
		$obj = new \StdClass();
		$obj->monitoring_lists = $monitoring_lists;
		$query=[];
		$options=['upsert'=>true];
		$this->db->monitoring_lists->updateOne($query,['$set'=>$obj],$options);
		dump("***************************");
		dump("Product Data Sync - success");
		dump("***************************");
		
		$this->DumpInfo();
	}
}