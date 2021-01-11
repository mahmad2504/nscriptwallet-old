<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Product extends Cveportal{
	public $scriptname = 'cveportal:product';
	private $admin=null;
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
	public function TimeToRun($update_every_xmin=10)
	{
		return true;
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
		//$this->db->products->drop();
		//$this->options['email']=0;// no emails when rebuild
	}
	public function Script()
	{
		$data = $this->data;
		$monitoring_lists = [];
		$groups = [];
		if(!isset($data->monitoring_lists))
		{
			dd("monitoring_lists not declared");
			
		}
		if(!isset($data->products))
		{
			dd("products not declared");
			
		}
		if(!isset($data->groups))
		{
			dd("groups not declared");
		}
		foreach($data->groups as $group)
		{
			$groups[$group->label] = $group->name;
		}
		foreach($data->monitoring_lists as $monitoring_list)
		{
			$monitoring_lists[$monitoring_list->label] = $monitoring_list->id;
		}
		
		foreach($data->products as $product)
		{
			if(!isset($groups[$product->group]))
			{
				dd('Product Group "'.$product->group."' is not declared as group");
			}
			$product->group = $groups[$product->group];
			/////////////////////////////////////////////////////////////
			if(!isset($monitoring_lists[$product->id]))
			{
				dd('Product "'.$product->id."' is not declared as monitoring list");
			}
			$product->id = $monitoring_lists[$product->id];
			////////////////////////////////////////////////////////////
			if(isset($product->parents))
			{
				$i=0;
				foreach($product->parents as $parent)
				{
					if(!isset($monitoring_lists[$parent]))
						dd('Products "'.$product->name.'" parent "'.$parent.'" is not declared as monitoring list');
					$product->parents[$i++]=$monitoring_lists[$parent];
				}
			}
			else
				$product->parents = [];
		}
		$this->db->products->drop();
		foreach($data->products as $product)
		{
			$this->DbPut($product);
		}
		$monitoring_lists = array_values($monitoring_lists);
	
		$obj = new \StdClass();
		$obj->monitoring_lists = $monitoring_lists;
		$query=[];
		$options=['upsert'=>true];
		$this->db->monitoring_lists->updateOne($query,['$set'=>$obj],$options);
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
	function GetProducts()
	{
		$query=[];
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
	function GetGroupNames()
	{
		$products = $this->GetProducts();
		if($products == null)
			return [];
		$p = new Product();
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
	function GetProductNames($groupname)
	{
		$query=['group'=>$groupname];
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
	function GetVersionNames($group_name,$productname)
	{
		$query=['group'=>$group_name,'name'=>$productname];
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
	public function GetIds($groupname=null,$productname=null,$versionname=null)
	{
		$products = $this->GetCveProducts($groupname,$productname,$versionname);
		$ids = [];
		foreach($products as $product)
		{
			$ids[$product->id] = $product->id;	
		}
		return array_values($ids);
	}
	public function GetCveProducts($groupname=null,$productname=null,$versionname=null)
	{
		$query = [];
		if($groupname!=null)
			$query['group'] = new Regex(preg_quote($groupname), 'i');
		if($productname!=null)
			$query['name'] = new Regex(preg_quote($productname), 'i');
		if($versionname!=null)
			$query['version'] = $versionname;//new Regex(preg_quote("".$versionname), 'i');	
		if($this->admin!=null)
			$query['admin'] = new Regex(preg_quote("".$this->admin), 'i');
		
		$options = [
			'projection'=>
					["_id"=>0,
					]
		];
		$list = $this->db->products->find($query,$options)->toArray();
		return $list;
	}
}