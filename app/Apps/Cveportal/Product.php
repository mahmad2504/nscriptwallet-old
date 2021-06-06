<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use \MongoDB\BSON\Regex;

class Product extends Cveportal{
	public $scriptname = 'cveportal_product';
	public function __construct($options)
    {
		$this->namespace = __NAMESPACE__;
		parent::__construct($options);
    }
	public function TimeToRun($update_every_xmin=1440)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	
	public function Rebuild()
	{
			
	}
	public function GetGroups($query=[])
	{
		$products =  $this->GetProducts($query,['_id'=>0,'group'=>1],'group');
		return $products;
	}
	public function GetNames($query=[])
	{
		$products = $this->GetProducts($query,['_id'=>0,'name'=>1],'name');
		return $products;
	}
	public function GetVersions($query=[])
	{
		$products = $this->GetProducts($query,['_id'=>0,'version'=>1],'version');
		return $products;
	}
	public function GetProducts($query=[],$projection=[],$distinct=null)
	{
		if($distinct == null)
		{
			$obj = $this->db->products->find($query,['projection'=>$projection]);
			return $obj->toArray();
		}
		else
		{
			$obj = $this->db->products->distinct($distinct, $query,['projection'=>$projection]);
			return $obj;
		}
	}
	public function Search($groupname=null,$productname=null,$versionname=null,$admin=null,$external=null)
	{
		$query = [];
		if($groupname!=null)
			$query['group'] = new Regex(preg_quote($groupname), 'i');
		if($productname!=null)
			$query['name'] = new Regex(preg_quote($productname), 'i');
		if($versionname!=null)
			$query['version'] = $versionname;//new Regex(preg_quote("".$versionname), 'i');	
		if($external != null)
			$query['external'] = $external;
		if($admin!=null)
			$query['admin'] = new Regex(preg_quote("".$admin), 'i');
		
		return $this->GetIds($query);
	}
	public function GetIds($query=[])
	{
		$retval =  $this->GetProducts($query,['_id'=>0,'id'=>1],'id');
		return $retval;
	}
	public function DumpInfo()
	{
		$groups= $this->GetGroups();
		$names = [];
		$versions = [];
		foreach($groups as $group)
		{
			dump($group);
			$names = $this->GetNames(["group"=>$group]);
			foreach($names as $name)
			{
				$versions = $this->GetVersions(['group'=>$group,'name'=>$name]);
				foreach($versions as $version)
				{
					$products = $this->GetProducts(["group"=>$group,"name"=>$name,"version"=>$version]);
					foreach($products as $p)
					{
						dump("    ".$p->name." ".$p->version."[id=".$p->id."][external=".$p->external."][Lock=".$p->lock."]");
					}
				}
			}
		}
	}
	
	public function LastUpdated()
	{
		return $this->ReadUpdateTime('product_update');
	}
	
	
	public function Script()
	{
		$data = json_decode(file_get_contents($this->producturl));
		if($data->status != 'ok')
		{
			dd('Error in product update');
			return ;
		}
		$monitoring_lists= [];
		foreach($data->data as $product)
		{
			$monitoring_lists[$product->id] = $product->id;
			foreach($product->parents as $pid)
				$monitoring_lists[$pid]=$pid;
				
			$query=['id'=>$product->id];
			$options=['upsert'=>true];
			$this->db->products_temp->updateOne($query,['$set'=>$product],$options);
		}	
		$this->db->products->drop();
		$this->mongo->admin->command(['renameCollection'=>$this->dbname.'.products_temp','to'=>$this->dbname.'.products']);
		$this->db->products_temp->drop();		
		$this->SaveUpdateTime('product_update');
		
		//$obj = $this->db->products->distinct('group',[],['projection'=>['group'=>1]]);
		//dd($obj);
		$this->DumpInfo();
	}
}