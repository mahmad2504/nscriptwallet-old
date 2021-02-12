<?php
namespace App\Apps\Bspestimate;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Bspestimate extends App{
	public $timezone='Asia/Karachi';
	public $scriptname='bspestimate';
	public $urls = [
	'4'=>'https://alm-jenkins-01.alm.mentorg.com:8443/job/nucleus_4x_driver_catalogue/lastSuccessfulBuild/artifact/nucleus_4x_driver_catalogue.xml/*view*/',
	'3'=>'https://alm-jenkins-01.alm.mentorg.com:8443/job/nucleus_4x_driver_catalogue/lastSuccessfulBuild/artifact/nucleus_4x_driver_catalogue.xml/*view*/',
	
	];
	public $url_common_tasks='https://script.google.com/macros/s/AKfycby074FpMytBr1yanhNyphyIwKm8Ce6yUv6gvOvr8objN6y5WJL9C1Dn/exec?resource=common_tasks';
	public $url_driver_estimates='https://script.google.com/macros/s/AKfycby074FpMytBr1yanhNyphyIwKm8Ce6yUv6gvOvr8objN6y5WJL9C1Dn/exec?resource=driver_estimates';
	
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
	function SaveInCatalog($driver)
	{
		$query=['id'=>$driver->id];
		$options=['upsert'=>true];
		$this->db->catalog->updateOne($query,['$set'=>$driver],$options);
	}
	public function SearchCatalog($identifier)
	{
		$query =['identifiers' => $identifier];
		$records = $this->MongoRead('catalog',$query,[],[]);
		return $records->toArray();
	}
	/*public function GetDriver($query)
	{
		$records = $this->MongoRead('drivers',$query,[],[]);
		$a =  $records->toArray();
		if(count($a) == 0)
			return null;
		else
			return $a[0];
	}*/
	public function GetDrivers($query=[])
	{
		
		$options = ['_id' => -1];
		$records = $this->MongoRead('catalog',$query,$options,[]);
		return $records->toArray();
	}
	
	public function ImportConfiguration()
	{
		$common_tasks = json_decode(file_get_contents($this->url_common_tasks));
		$i=0;
		foreach($common_tasks as $task)
		{
			$task->id = $i++;
			$field = '0';
			$task->team = strtolower($task->$field);
			unset($task->$field);
			
			$field = '1';
			$task->title = $task->$field;
			unset($task->$field);
			
			$field = '2';
			if(strtolower($task->$field)=='yes')
				$task->immuteable = 1;
			else
				$task->immuteable = 0;
			unset($task->$field);
			
			$field = '3';
			$task->estimate = $task->$field;
			unset($task->$field);
			
		}
		$obj =  new \StdClass();
		$obj->id = 'common_tasks';
		$obj->data = $common_tasks;
		$this->Save($obj,'tasks');
		
		
		
		$drivers = json_decode(file_get_contents($this->url_driver_estimates));
		$i=0;
		$current_driver = null;
		$driver_list = [];
		foreach($drivers as $driver)
		{
			$driver->id = $i++;
			$field = '0';
			$driver->class = $driver->$field;
			unset($driver->$field);
			
			$field = '1';
			$driver->type = strtolower($driver->$field);
			unset($driver->$field);
			
			if($driver->type == 'driver')
			{
				$current_driver = $driver;
				$current_driver->children = [];
				$driver_list[] = $current_driver;
				$driver->parent = -1;
			}
			else
			{
				$driver->class = $current_driver->class;
				$driver->parent = $current_driver->id;
				$current_driver->children[] = $driver;
			}
			$field = '2';
			$driver->name = $driver->$field;
			unset($driver->$field);
			
	
			$field = '3';
			$driver->dev_estimate_new = $driver->$field;
			if($driver->dev_estimate_new == '')
				$driver->dev_estimate_new = 0;
			unset($driver->$field);
			$field = '4';
			$driver->qa_estimate_new = $driver->$field;
			if($driver->qa_estimate_new == '')
				$driver->qa_estimate_new = 0;
			unset($driver->$field);
			
			
			$field = '5';
			$driver->dev_estimate_4_to_4 = $driver->$field;
			if($driver->dev_estimate_4_to_4 == '')
				$driver->dev_estimate_4_to_4 = 0;
			unset($driver->$field);
			$field = '6';
			$driver->qa_estimate_4_to_4 = $driver->$field;
			if($driver->qa_estimate_4_to_4 == '')
				$driver->qa_estimate_4_to_4 = 0;
			unset($driver->$field);
			
			
			$field = '7';
			$driver->dev_estimate_3_to_4 = $driver->$field;
			if($driver->dev_estimate_3_to_4 == '')
				$driver->dev_estimate_3_to_4 = 0;
			unset($driver->$field);
			$field = '8';
			$driver->qa_estimate_3_to_4 = $driver->$field;
			if($driver->qa_estimate_3_to_4 == '')
				$driver->qa_estimate_3_to_4 = 0;
			unset($driver->$field);
			
			$field = '9';
			$driver->dev_estimate_3_to_3 = $driver->$field;
			if($driver->dev_estimate_3_to_3 == '')
				$driver->dev_estimate_3_to_3 = 0;
			unset($driver->$field);
			$field = '10';
			$driver->qa_estimate_3_to_3 = $driver->$field;
			if($driver->qa_estimate_3_to_3 == '')
				$driver->qa_estimate_3_to_3 = 0;
			unset($driver->$field);
		}
		$obj =  new \StdClass();
		$obj->id = 'driver_estimates';
		$obj->data = $driver_list;
		$this->Save($obj,'estimates');
	}
	public function ImportDriverCatalog()
	{
		$classes = [];
		foreach($this->urls as $label=>$url)
		{
			$data = explode("\n",file_get_contents($url));
			{
				
				foreach($data as $d)
				{
					
					$parts = explode('Driver Class:',$d);
					if(count($parts)==2)
					{
						$class = explode('</class>',$parts[1])[0];
						$classes[$class] = $class;
					}
					else
					{ 
						$parts = explode('::',$d);
						if(count($parts)==2)
						{
							$driver =  new \StdClass();
							$driver->name = trim(explode('<IP>',$parts[0])[1]);
							$driver->product = trim($label);
							$driver->class = trim($class);
							$driver->id = md5($driver->name.$driver->product.$driver->class);
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
							/*dump($driver->class);
							dump($driver->name);
							dump($driver->identifiers);*/
							$this->SaveInCatalog($driver);
						}
					}
				}
			}
		}
	}
	public function GetTeamTasks($team)
	{
		$ct = $this->Read('common_tasks','tasks');
		$result = [];
		foreach($ct->data as $t)
		{
			if($t->team == $team)
				$result[] = $t;
		}
		return $result;
	}
	public function GetDriverEstimates()
	{
		$result = $this->Read('driver_estimates','estimates');
		
		return $result->data;
	}
	public function Script()
	{
		dump("Running script");
		$this->ImportDriverCatalog();
		$this->ImportConfiguration();
	}
}