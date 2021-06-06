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
			$this->datafolder = "D://OSS//comfort-2.3";//data cryptography";
		}
		else
			$this->datafolder = "D://OSS//comfort-2.3";//.. data/cryptography";
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
	function SearchHits($package,$line_text,$line_text_after_1,$line_text_after_2,$line_text_after_3)
	{
		$query = [];
		$query['package'] = str_replace(" ","+",$package);
		$query['line_text'] = $line_text;
		$query['line_text_after_1'] = $line_text_after_1;
		$query['line_text_after_2'] = $line_text_after_2;
		$query['line_text_after_3'] = $line_text_after_3;
		
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
	function UpdateProjects($file_identifier)
	{
		$query=['packages.files.file_identifier'=>$file_identifier];
		$projects = $this->db->projects->find($query);
		if($projects == null)
			return null;
		$projects =  $projects->toArray();
		foreach($projects as $project)
		{
			$project->triaged=0;
			$project->suspicios = 0;
			foreach($project->packages as $package)
			{
				$package->triaged = 0;
				$package->suspicios = 0;
				foreach($package->files as $file)
				{
					if($file->file_identifier == $file_identifier)
					{
						$file->triaged = 0;
						$file->suspicios = 0;
						$hits = $this->ReadHits(null,null,$file_identifier);
						foreach($hits as $hit)
						{
							$triage = $this->ReadTriage($hit->id);
							$file->triaged += $triage->triaged;
							$file->suspicios += $triage->suspicious;
							
						}
				
					}
					$package->triaged += $file->triaged;
					$package->suspicios += $file->suspicios;
				}
				$project->triaged += $package->triaged;
				$project->suspicios += $package->suspicios;
			}
			$this->SaveProject($project);
		}
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
	function SaveTriage($triage)
	{
		$query=['id'=>$triage->id];
		$options=['upsert'=>true];
		$this->db->triage->updateOne($query,['$set'=>$triage],$options);
	}
	function ReadTriage($id)
	{
		$query=['id'=>$id];
		$triage = $this->db->triage->findOne($query);
		if($triage == null)
		{
			$o = new \StdClass();
			$o->id = $id;
			$o->text = '';
			$o->comment = '';
			$o->triaged = 0;
			$o->suspicious = 0;
			$this->SaveTriage($o);
			$triage = $this->db->triage->findOne($query);
		}
		return $triage->jsonSerialize();
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
					//$package  = basename($fileinfo->getFilename());
					//dump($package);
					//if	(($package == 'linux-base-4.5')||
					//	($package == 'linux-4.14.195-4.14.195')
					//	)
					//continue;
					
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
					$f = strtolower(basename($value->file_paths[0]));
					
					if( ( $f!='readme')&&( $f!='license'))
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
						($ext == 'py')||
						($ext == 'sh')||
						($ext == 'rst')||
						($ext == 'copyright')||
						($ext == 'templates')
						)
					{
						//dump($value->file_paths[0]."  ".count($value->hits));
					}
					else
					{
						continue;
					}
					}
					//dump($value->file_paths[0]);
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
					$last_line = -100;
					foreach($value->hits as $hit)
					{
						//if($i==20)
						//	break;
						if($hit->line_number <= $last_line+3)
						    continue;
						$last_line = $hit->line_number;
						$i++;
						
						$id = md5($file.$hit->line_number.$hit->line_text);
						if(isset($shits[$id]))
						{
							$hit = $shits[$id];
						}
						else
						{	
							$hit->file = $file;
							$hit->file_identifier = [];
							$hit->package = [];
							$hit->location = [];
							$hit->id =$id;
						}
						$fd = md5($package.$file);
						$found=0;
						$save=0;
		
						$found=0;
						foreach($hit->package as $pkg)
						{
							if($pkg == $package)
								$found=1;
						}
						if(!$found)
						{
							$hit->package[] = $package;
							$save = 1;
						}
						
						$found=0;
						foreach($hit->file_identifier as $file_identifier)
						{
							if($file_identifier == $fd)
								$found=1;
						}
						if(!$found)
						{
							$hit->file_identifier[] = $fd;
							$save = 1;
						}
							
						$found=0;
						foreach($hit->location as $loc)
						{
							if($loc == $value->file_paths[0])
								$found=1;
						}
						if(!$found)
						{
							$hit->location[] = $value->file_paths[0];
							$save = 1;
						}
						if(isset($ids[$hit->id]))
						{
							continue; // already created
						}
						$ids[$hit->id] = $hit->id;
						$triage = $this->ReadTriage($hit->id);
						if($triage->triaged==1)
							$file_object->triaged++;
						
						if($triage->suspicious==1)
							$file_object->suspicios++;
							
						$file_object->hits++;
						if($save == 1)
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
				$package->triaged += $file->triaged;
				$package->suspicios += $file->suspicios;
				$package->hits += $file->hits;
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