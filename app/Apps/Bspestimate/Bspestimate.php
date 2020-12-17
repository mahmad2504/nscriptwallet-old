<?php
namespace App\Apps\Bspestimate;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Bspestimate extends App{
	public $timezone='Asia/Karachi';
	public $urls = [
	'nucleus 4.x'=>'https://alm-jenkins-01.alm.mentorg.com:8443/job/nucleus_4x_driver_catalogue/lastSuccessfulBuild/artifact/nucleus_4x_driver_catalogue.xml/*view*/',
	'nucleus 3.x'=>'https://alm-jenkins-01.alm.mentorg.com:8443/job/nucleus_4x_driver_catalogue/lastSuccessfulBuild/artifact/nucleus_4x_driver_catalogue.xml/*view*/'
	
	];
	
	//public $query='labels in (risk,milestone) and duedate >=';
	//public $jira_fields = ['key','status','statuscategory','summary']; 
    //public $jira_customfields = ['sprint'=>'Sprint'];  	
	//public $jira_server = 'EPS';
	
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);

    }
	
	public function GetProducts()
	{
		$data = [];
		foreach($this->urls as $label=>$url)
		{
			$data[] = $label;
		}
		return $data;
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
	public function Rebuild()
	{
		$this->db->drivers->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	function SaveDriver($driver)
	{
		$query=['id'=>$driver->id];
		$options=['upsert'=>true];
		$this->db->drivers->updateOne($query,['$set'=>$driver],$options);
	}
	public function SearchDrivers($identifier)
	{
		$query =['identifiers' => $identifier];
		$records = $this->MongoRead('drivers',$query,[],[]);
		return $records->toArray();
	}
	public function GetDriver($id)
	{
		$query =['id' => $id];
		$records = $this->MongoRead('drivers',$query,[],[]);
		$a =  $records->toArray();
		if(count($a) == 0)
			return null;
		else
			return $a[0];
	}
	public function GetDrivers($product=null)
	{
		$query = [];
		if($product != null)
			$query =['product' => $product];
		$records = $this->MongoRead('drivers',$query,[],[]);
		return $records->toArray();
	}
	public function Script()
	{
		dump("Running script");
		$class = null;
		foreach($this->urls as $label=>$url)
		{
			$data = explode("\n",file_get_contents($url));
			foreach($data as $d)
			{
				$parts = explode('Driver Class:',$d);
				if(count($parts)==2)
				{
					$class = explode('</class>',$parts[1])[0];
					//dump("Class = ".$class);
				}
				else
				{ 
					$parts = explode('::',$d);
					if(count($parts)==2)
					{
						$driver =  new \StdClass();
						$driver->name = explode('<IP>',$parts[0])[1];
						$driver->product = $label;
						$driver->id = md5($driver->name.$driver->product);
						$driver->class = $class;
						$driver->estimates = [];
						$sdriver = $this->GetDriver($driver->id);
						if($sdriver  == null)
							$sdriver = $driver;
						$targets = $this->GetProducts();
						foreach($targets as $t)
						{
							$l = md5($t);
							if(!isset($sdriver->estimates[$l]))
								$driver->estimates[$l]=0;
							else
								$driver->estimates[$l]=$sdriver->estimates[$l];
						}
						
						//dump("Driver ".$driver->name);
						$driver->identifiers = [];
						
						$ids = explode('</IP>',$parts[1])[0];
						$ids = explode(" ",$ids);
						foreach($ids as $id)
						{
							if(strlen(trim($id))!=0)
							{
								$driver->identifiers[] = str_replace('"','',$id);
							}
						}
						
						$this->SaveDriver($driver);
					}
				}
			}
		}
	}
}