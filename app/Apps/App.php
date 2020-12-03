<?php
namespace App\Apps;
use App\Email;
use \MongoDB\Client;
use \MongoDB\BSON\UTCDateTime;

class App
{
	public $key = 'Unknown';
	public function __construct($namespace,$server)
	{
		$parts = explode("\\",$namespace);
		$key = strtolower($parts[count($parts)-1]);
		$mongoclient =new Client($server);
		$this->db = $mongoclient->$key;
		$this->key = $key;
	}
	public function Permission($update_every_xmin=1)
	{
		$sec = $this->SecondsSinceLastUpdate();
		if($sec == null)
		{
			$this->SaveUpdateTime();
			dump("First update");
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
				dump("timeout #",$timeout);
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
	  	$email = new Email('localhost',['support-bot@mentorg.com', 'Support Bot'],'Mumtaz_Ahmad@mentor.com');
		$subject = strtoupper($this->key)." : Service Status Alert";
		$email->AddTo('mumtaz_ahmad@mentor.com');
		$msg = 'Service '.strtoupper($this->key).' has some issue and not updating  ';
		$email->Send($subject,$msg);
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
}