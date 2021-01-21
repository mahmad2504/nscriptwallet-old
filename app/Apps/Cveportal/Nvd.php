<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use \MongoDB\BSON\UTCDateTime;

class Nvd extends Cveportal{
    private $datafolder = "data/cveportal/nvd";
	public $scriptname = "cveportal:nvd";
	public $urls = [
			"https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2020.json.zip",	
		    "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2019.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2018.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2017.json.zip",	
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2016.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2015.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2014.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2013.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2012.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2011.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2010.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2009.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2008.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2007.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2006.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2005.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2004.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2003.json.zip",
            "https://nvd.nist.gov/feeds/json/cve/1.1/nvdcve-1.1-2002.json.zip",
        ];
	public function __construct($options=null)
    {
		ini_set("memory_limit","2000M");
		set_time_limit(3000);
		
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		if(!file_exists($this->datafolder))
			mkdir($this->datafolder, 0, true);
		parent::__construct($this);

    }
	public function TimeToRun($update_every_xmin=10)
	{
		return true;
		return parent::TimeToRun($update_every_xmin);
	}
	
	public function Rebuild()
	{
		$this->db->nvd->drop();
		//$this->options['email']=0;// no emails when rebuild
	}
     
	function GetContentSize($url) 
	{
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_FILETIME, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, true);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION,true);
		
		$header = curl_exec($curl);
		$info = curl_getinfo($curl);
		curl_close($curl);
		return $info['download_content_length'];
	}
	function Download($url,$filename)
	{
		$zip = new \ZipArchive;
		$ch = curl_init(); 
		dump('Downloading '.basename($url));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		//curl_setopt($ch, CURLOPT_SSLVERSION,3);
		$data = curl_exec ($ch);
		$error = curl_error($ch); 
		curl_close ($ch);
		$file = fopen($filename, "w");
		fputs($file, $data);
		fclose($file);
		//SendConsole(time(),'Unzipping '); 
		if ($zip->open($filename ) === TRUE) 
		{
			$zip->extractTo($this->datafolder."/");
			$zip->close();
			//SendConsole(time(),'Done '.basename($url) ); 
		} 
		else 
		{
			dump('Failed '.basename($url));
			dump("Nothing is updated");
			exit();
		}	
	}
	private function UpdateDatabase($nvdurl)
	{
		$filename = str_replace('.zip','',basename($nvdurl));
		//echo memory_get_usage() . "\n";
		
		$data = $this->PreProcess($this->datafolder."/".$filename);
		$data_array = [];
		foreach($data->CVE_Items as $cve)
		{
			$data_array[] =  $cve;
			if(count($data_array) > 2000)
			{
				$this->db->nvd->insertMany($data_array);
				$data_array = [];
			}
		}
		//echo $filename." ".$this->db;
		//echo memory_get_usage() . "\n";
		//Utility::Console(time(),"...."); 
		if(count($data_array) > 0)
		{
			$this->db->nvd->insertMany($data_array);
			$data_array = [];
		}
		//$this->collection->insertMany($data);	
	}
	private function  PreProcess($filename)
	{
		$data = file_get_contents($filename);
		//echo memory_get_usage()."\n";
		$json = json_decode($data);
		//echo memory_get_usage()."\n";
		foreach($json->CVE_Items as $cve)
		{
			$date = new \DateTime($cve->publishedDate);
			$date->setTime(0,0,0);
			$ts = $date->getTimestamp();
			$cve->publishedDate = new UTCDateTime($ts*1000);
			$date = new \DateTime($cve->lastModifiedDate);
			$date->setTime(0,0,0);
			$ts = $date->getTimestamp();
			$cve->lastModifiedDate = new UTCDateTime($ts*1000);
			//$cve->publishedDate = new MongoDB\BSON\Timestamp(1, $ts);
			//echo $date->__toString();
			//echo $cve->publishedDate;
			//exit();
		}
		dump("Updating ".$filename." data in database"); 
		return $json;	
	}
	public function Script()
	{
		$updatecvedb = true;
		$collections = $this->db->listCollections();
		$collectionNames = [];
		foreach ($collections as $collection) 
		{
			$name = $collection->getName();
			if($name == 'nvd')
			{
				$updatecvedb = false;
			}
		}
		foreach($this->urls  as $url)
		{
			dump("Checking ".basename($url)." feed"); 
			$contentsize = $this->GetContentSize($url);
            $filename = basename($url);
			$filename = $this->datafolder."/".$filename;
			$oldcontentsize = 0;
			if(file_exists($filename))
			{
				$oldcontentsize = filesize($filename);
			}

			if($oldcontentsize!=$contentsize)
			{
				$this->Download($url,$filename);
				$updatecvedb = true;
			}
		}
		if($updatecvedb)
		{
			dump("Updating cve database"); 
			$this->db->nvd->Drop();
			foreach($this->urls as $nvdurl)
				$this->UpdateDatabase($nvdurl);
			dump("Updating Search Indexes"); 
			//Create Text Index
			$this->db->nvd->createIndex(["configurations.nodes.cpe_match.cpe23Uri"=>'text',"configurations.nodes.children.cpe_match.cpe23Uri"=>'text']);
			//Create Index
			$this->db->nvd->createIndex(["cve.CVE_data_meta.ID"=>1]);
		}
		//else
		//   dump('NVD Data already updated');
	}
	function GetCve($cve_number)
	{
		$query = ['cve.CVE_data_meta.ID'=>$cve_number];
		$projection = ['projection'=>[ 
			'_id'=>0,
			'cve.CVE_data_meta.ID'=>1,
			'lastModifiedDate'=>1,
			'publishedDate'=>1,
			'cve.description.description_data.value'=>1,
			'impact.baseMetricV3.cvssV3'=>1,
			'impact.baseMetricV2.cvssV2'=>1
			]];
		$cve_nvd_data = $this->db->nvd->findOne($query,$projection);
		return $cve_nvd_data;
	}
}