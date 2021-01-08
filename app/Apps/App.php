<?php
namespace App\Apps;
use App\Email;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;
use App\Libs\Jira\Jira;
use App\Libs\Jira\Fields;
use Carbon\Carbon;
class App
{
	public $key = 'Unknown';
	public $app = null;
	public $jira_fields = [];
	public $jira_customfields = [];
	public $timezone =  null;
	public $mongo = null;
	public $fields = null;
	public $scriptname = 'unnamed';
	public function InitOption()
	{
		if(!isset($this->options))
			$this->options = [];
		if(!isset($this->options['rebuild']))
			$this->options['rebuild'] = 0;
		if(!isset($this->options['force']))
			$this->options['force'] = 0;
		if(!isset($this->options['email']))
			$this->options['email'] = 2;
		if(!isset($this->options['email_resend']))
			$this->options['email_resend'] = 0;
		
	}
	public function __construct($app)
	{
		$this->InitOption();
		$this->app = $app;
		if(!isset($this->namespace))
			dd("App namespace not set");
		$parts = explode("\\",$app->namespace);
		$key = strtolower($parts[count($parts)-1]);
		$this->key = $key;
		$this->dbname=$key;
		if(isset($this->timezone))
			date_default_timezone_set($this->timezone);
		
		if(!isset($this->mongo_server))
			dd("App mongo_server not set");
		
		$mongoclient =new Client($this->mongo_server);
		$this->mongo = $mongoclient;
		$this->db = $mongoclient->$key;
		if(isset($this->jira_server))
		{
			$this->fields = new Fields($this);
			Jira::Init($app);
			if(!$this->fields->Exists()||$this->options['rebuild'])
			{
				dump("Configuring Jira Fields");
				$this->fields = new Fields($this,0);
				$this->fields->Set($this->jira_fields);
				if($this->isAssoc($this->jira_customfields))
					$this->fields->Set($this->jira_customfields);
				$this->fields->Dump();
			}
		}
	}
	private function isAssoc(array $arr)
	{
		if (array() === $arr) return false;
		return array_keys($arr) !== range(0, count($arr) - 1);
	}
	function IssueParser($code,$issue,$fieldname)
	{
		dd("Implement IssueParser function");
	}
	public function JiraFields(&$fields,&$customfields)
	{
		dd("Implement JiraFields function");
	}
	public function Rebuild()
	{
		dump('Rebuild callback function not implemented');
	}
	public function Options()
	{
		$options = [];
		foreach($this->options as $key=>$value)
		{
			if(($key == 'help')||
			   ($key == 'version')||
			   ($key == 'version')||
			   ($key == 'quiet')
			   )
				continue;
			$options["--".$key] = $value;
		}
		return $options	;	
	}
	public function Run()
	{
		if($this->app->TimeToRun())
		{
			if($this->options['rebuild'])
				$this->Rebuild();
			dump("#########  Running script ".$this->scriptname."  #########");
			$this->Script();
			$this->SaveUpdateTime();
			$this->Save(['sync_requested'=>0]);
			dump("Done");
		}
	}
	public function Script()
	{
		dd("Implement Scriot function");
	}
	public function TimeToRun($update_every_xmin=1)
	{
		$sync_requested = $this->Read('sync_requested');
		if($this->options['rebuild']||$this->options['force']||$sync_requested)
			return true;
		
		$sec = $this->SecondsSinceLastUpdate();
		if($sec == null)
		{
			$this->SaveUpdateTime();
			return true;
		}
		if($sec >=  $update_every_xmin*3*60)
		{
			$timeout=$this->Read('timeout');
		
			if($timeout==null)
				$timeout=0;
			if($timeout==2)
			{
				$this->Notify('mumtaz_ahmad@mentor.com');
				dump("Sending Service Error Notification [".round($sec/60)."] minutes gone since last update");
				$timeout++;
				$this->Save(compact('timeout'));
				return false;
			}
			else
			{
				$timeout++;
				dump("timeout #".$timeout);
				$this->Save(compact('timeout'));
				return true;
			}
		}
		if($sec >=  $update_every_xmin*60)
		{
			$timeout = 0;
			$this->Save(compact('timeout'));
			dump("Updating after [".round($sec/60)."] minutes");
			return true;
		}
		dump("Its not time to update.[".$sec."] seconds gone since last update");
		return false;
	}
	public function Notify($to)
	{
	  	$email = new Email();
		$subject = strtoupper($this->key)." : Service Status Alert";
		$msg = 'Service '.strtoupper($this->key).' has some issue and not updating  ';
		$email->Send(2,$subject,$msg);
	}
	public function SecondsSinceLastUpdate()
	{
		if($this->ReadUpdateTime()==null)
			return null;
		$ldt = new \DateTime($this->ReadUpdateTime());
		$ldt = $ldt->getTimestamp();
		$cdt = $this->CurrentDateTime();
		return $cdt - $ldt;
	}
	public function CurrentDateTime()
	{
		$now =  new \DateTime();
		$now->setTimezone(new \DateTimeZone($this->timezone));
		return $now->getTimestamp();
	}
	public function CurrentDateTimeObj()
	{
		$now =  new Carbon('now');
		$now->setTimezone(new \DateTimeZone($this->timezone));
		return $now;
	}
	public function TimestampToObj($timestamp)
	{
		$dt =  new Carbon('now');
		$dt->setTimeStamp($timestamp);
		$dt->setTimezone(new \DateTimeZone($this->timezone));
		return $dt;
	}
	public function SetTimeZone($datetime)
	{
		$datetime->setTimezone(new \DateTimeZone($this->timezone));
	}
	function Save($obj)
	{
		
		$options=['upsert'=>true];
		if(is_array($obj))
		{
			foreach($obj as $key=>$value)
			{
				$query=['id'=>$key];
				$o = new \StdClass();
				$o->id = $key;
				$o->_value=$value;
				$this->db->settings->updateOne($query,['$set'=>$o],$options);
			}
		}
		else
		{
			$query=['id'=>$obj->id];
			$this->db->settings->updateOne($query,['$set'=>$obj],$options);
		}
	}
	function Read($id)
	{
		$query=['id'=>$id];
		$obj = $this->db->settings->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		if(isset($obj->_value))
			return $obj->_value;
		unset($obj->_id);
		return $obj;
	}
	public function MongoRead($collection,$query,$sort=[],$projection=[],$limit=-1)
	{
		$query = $query;
		$options = ['sort' => $sort,
					'projection' => $projection,
					];
		if($limit != -1)
			$options['limit'] = $limit;
		
		$cursor = $this->db->$collection->find($query,$options);
		return $cursor;
	}
	
	public function SaveUpdateTime()
	{
		$obj = new \StdClass();
		$obj->date =  new \DateTime();
		$obj->date = $obj->date->format('Y-m-d H:i:s');
		$obj->id = 'lastupdate';
		$lastupdate = $this->Save($obj);
	}
	public function ReadUpdateTime()
	{
		$ud = $this->Read('lastupdate');
		if($ud ==  null)
			return null;
		else
			return $ud->date;
		
	}
	public function Get($query)
	{
		$data = null;
		$query = str_replace(" ","%20",$query);
		$resource=$query;
		
		$curl = curl_init();
		curl_setopt_array($curl, array(
		CURLOPT_USERPWD => 'mahmad:NDExMTk0Njk2ODAyOts/IfG8+FgNSlBMKSxk21NIYx/U',
		CURLOPT_URL =>$resource,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array('Content-type: application/json')));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		if($data != null)
		{
			curl_setopt_array($curl, array(
				CURLOPT_POST => 1,
				CURLOPT_POSTFIELDS => $data
				));
		}
		$result = curl_exec($curl);
		
		$code = curl_getinfo ($curl, CURLINFO_HTTP_CODE);
		if($code == 200)
			return json_decode($result);
		return 0;
	}
	function FetchJiraTickets($jql=null)
	{
		if($jql==null)
			return Jira::FetchTickets($this->query);
		else
			return Jira::FetchTickets($jql);
	}
	function GetBusinessMinutes($ini_stamp,$end_stamp,$start_hour,$end_hour)
	{
		$ini = new \DateTime();
		$ini->setTimeStamp($ini_stamp);
		$ini->setTimezone(new \DateTimeZone($this->timezone));
		
		$end = new \DateTime();
		$end->setTimeStamp($end_stamp);
		$end->setTimezone(new \DateTimeZone($this->timezone));
		
		return round(GetBusinessSeconds($ini,$end,$start_hour,$end_hour)/60);
	}
}