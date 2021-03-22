<?php
namespace App\Apps\Cryptography;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Cryptography extends App{
	public $timezone='Asia/Karachi';
	public $query='labels in (risk,milestone) and duedate >=';
	public $scriptname = 'cryptography';
	public $datafolder = '';
	public $options = 0;
	public function __construct($options=null)
        {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);

       }
	public function TimeToRun($update_every_xmin=10)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	public function InConsole($yes)
	{
		if($yes)
		{
			$this->datafolder = "D://Assignments//OSS//2.1.0//base";//data cryptography";
		}
		else
			$this->datafolder = "D://Assignments//OSS//2.1.0//base";//.. data/cryptography";
	}
	public function Rebuild()
	{
		//$this->db->cards->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	function ReadHit($id)
	{
		$query=['id'=>$id];
		$obj = $this->db->hits->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function ReadHits($package=null,$file=null,$file_identifier=null)
	{
		if($package != null)
			$query=['package'=>$file];
		
		if($file != null)
			$query=['file'=>$file];
		
		if($file_identifier != null)
			$query=['file_identifier'=>$file_identifier];
		
		
		$obj = $this->db->hits->find($query);
		if($obj == null)
			return null;
		return $obj->toArray();
	}
	function ReadProject($id=null,$name=null)
	{
		$query = [];
		if($id != null)
			$query=['id'=>$id];
		if($name != null)
			$query=['name'=>$name];
		$obj = $this->db->projects->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function SaveProject($project)
	{
		$query=['id'=>$project->id];
		$options=['upsert'=>true];
		$this->db->projects->updateOne($query,['$set'=>$project],$options);
	}
	function SaveHit($hit)
	{
		$query=['id'=>$hit->id];
		$options=['upsert'=>true];
		$this->db->hits->updateOne($query,['$set'=>$hit],$options);
	}
	public function PreProcess()
	{
		dump("Preprocessing folder ".$this->datafolder);
		$dir = new \DirectoryIterator($this->datafolder);
		foreach ($dir as $fileinfo) 
		{
			if (!$fileinfo->isDot()) 
			{
				if($fileinfo->isDir())
				{
					if(!file_exists($this->datafolder."/".$fileinfo->getFilename().'.crypto'))
					{
						$cmd = 'python  scan-for-crypto.py '.$fileinfo->getPathname()." -o ".$this->datafolder;
						dump($cmd);
						exec($cmd);
					}
				}
			}
		}
    }
	public function Script()
	{		
		dump("Running script");
		ini_set("memory_limit","5G");
		$this->PreProcess();
		$dir = new \DirectoryIterator($this->datafolder);
		$project = new \StdClass();
		$project->id = md5($this->datafolder);
		$project->name = basename ($this->datafolder);
		$p = $this->ReadProject($project->id);
		if($p  != null)
		{
			if($this->options['rebuild']==0)
				dd(	$this->datafolder." all ready exists");
		}
		$project->folder = $this->datafolder;
		$project->packages = [];
		foreach ($dir as $fileinfo) 
		{
			if (!$fileinfo->isDot()&&!$fileinfo->isDir()) 
			{
				$ext = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));
				if($ext != 'crypto')
					continue;
				$data = json_decode(file_get_contents($this->datafolder."/".$fileinfo->getFilename()));
				if(isset($data->imported))
				{
					if($this->options['rebuild']==0)
						continue;
				}
				$package = explode(".crypto",$fileinfo->getFilename())[0];
				
				if(!isset($project->packages[$package]))
				{
					$package_object = new \StdClass();
					$package_object->name = $package;
					$package_object->files = [];
					$project->packages[$package] = $package_object;
					
				}
				else
					$package_object = $project->packages[$package];
					
				
				//dump($this->ReadHits($package));
				if(!isset($data->crypto_evidence))
					continue;
				
				foreach ($data->crypto_evidence as $property => $value) 
				{
					$ext = strtolower(pathinfo($value->file_paths[0], PATHINFO_EXTENSION));
					if( ($ext == 'cc')||
						($ext == 'c')||
						($ext == 'h')||
						($ext == 'hh')||
						($ext == 'cpp')||
						($ext == 'hpp')||
						($ext == 'java')||
						($ext == 'ipp')||
						($ext == 'py')
						)
					{
						//dump($value->file_paths[0]."  ".count($value->hits));
					}
					else
					{
						//dump($ext);
						continue;
					}
					dump($value->file_paths[0]);
					$file  = explode($package,$value->file_paths[0])[1];
					
					if(!isset($package_object->files[$file]))
					{
						$file_object = new \StdClass();
						$file_object->path = $value->file_paths[0];
						$file_object->hits = 0;
						$file_object->triaged = 0;
						$file_object->suspicios = 0;
						$file_object->file_identifier = md5($package.$file);
						$package_object->files[$file] = $file_object;
					}
					else
						$file_object = $package_object->files[$file];
					
					$shits = $this->ReadHits($package,$file);
					$temp = [];
					foreach($shits as $shit)
					{
						$temp[$shit->id] =$shit;	
					}
					$shits = $temp;
					
					$i=0;
					$ids=[];
					foreach($value->hits as $hit)
					{
						if($i==20)
							break;
						$i++;
						$id = md5($file.$hit->file_index_begin);
						if(isset($shits[$id]))
						{
							$hit = $shits[$id];
						}
						else
						{	
							$hit->file = $file;
							$hit->file_identifier = md5($package.$file);
							$hit->package[]= $package;
							$hit->location = [];
							$hit->id =$id;
						}
						$found=0;
						foreach($hit->location as $loc)
						{
							if($loc == $value->file_paths[0])
								$found=1;
						}
						if(!$found)
							$hit->location[] = $value->file_paths[0];
							
						if(isset($ids[$hit->id]))
						{
							continue; // already created
						}
						$ids[$hit->id] = $hit->id;
						if(isset($hit->triaged))
							$file_object->triaged++;
						
						if(isset($hit->suspicios))
							$file_object->suspicios++;
							
						$file_object->hits++;
						if(!$found)
							$this->SaveHit($hit);
					}
				}
				$package_object->files =  array_values($package_object->files);
				
				if(!isset($data->imported))
				{
					$data = json_decode(file_get_contents($this->datafolder."/".$fileinfo->getFilename()));
					$data->imported = 1;
					$data = json_encode($data);
					file_put_contents($this->datafolder."/".$fileinfo->getFilename(),$data);
				}
			}
		}
		$project->packages = array_values($project->packages);
		$i=0;
		$del = [];
		
		$project->triaged = 0;
		$project->suspicios=0;
		$project->hits=0;
			
		foreach($project->packages as $package)
		{
			if(count($package->files)==0)
				$del[] = $i;
			$package->triaged = 0;
			$package->suspicios=0;
			$package->hits=0;
			foreach($package->files as $file)				
			{
				$package->triaged += $file_object->triaged;
				$package->suspicios += $file_object->suspicios;
				$package->hits += $file_object->hits;
			}
			$project->triaged += $package->triaged;
			$project->suspicios += $package->suspicios;
			$project->hits += $package->hits;
			$i++;
		}
		foreach($del as $d)
		{
			unset($project->packages[$d]);
		}
		$project->packages = array_values($project->packages);
		$this->SaveProject($project);
		
	}
}