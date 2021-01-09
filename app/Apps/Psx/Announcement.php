<?php
namespace App\Apps\Psx;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;

use Illuminate\Notifications\Notifiable;
use App\Notifications\Telegram;
use NotificationChannels\Telegram\TelegramMessage;

class Announcement extends Psx{
	use Notifiable;
	public $scriptname = 'psx:announcement';
	public $options = 0;
	public $url = "https://dps.psx.com.pk/announcements";
	public $notice_types=[
		'Company Notice'=>'C',
		'PSX Notice'=>'E',
		'CDC Notice'=>'A',
		'SECP Notice'=>'B',
		'NCCPL Notice'=>'D'
	];
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=60)
	{
		$scriptname = $this->scriptname;
		$alldone = $this->Read($scriptname.'_alldone');
		if($alldone)
			$update_every_xmin = 60;
		else
			$update_every_xmin = 5;
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		$scriptname = $this->scriptname;
		$this->db->$scriptname->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	function ReadRecord($id)
	{
		$query=['id'=>$id];
		$scriptname = $this->scriptname;
		$obj = $this->db->$scriptname->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function SaveRecord($result)
	{
		$scriptname = $this->scriptname;
		$query=['id'=>$result->id];
		$options=['upsert'=>true];
		$this->db->$scriptname->updateOne($query,['$set'=>$result],$options);
	}
	public function SendBasicTelegram($to,$record)
	{
		if($this->options['email'] != 1)
			return;
		
		$this->telegram = TelegramMessage::create()
        ->to($to)
        ->content($this->toBasicMessage($record));
		$this->telegram->button('View', 'https://mahmad2504.github.io/psx/subscribe.html');
		$this->notify(new Telegram());	
		dump("Sent basic telegram of id=".$record->id." to ".$to);
	}
	public function SendProTelegram($to,$record)
	{
		if($this->options['email'] != 1)
			return;
		
		$this->telegram = TelegramMessage::create()
        ->to($to)
        ->content($this->toProMessage($record));  
			
		if(isset($record->pdf))
		   $this->telegram->button('Pdf', 'https://dps.psx.com.pk/'.$record->pdf);
		if(isset($record->image))
		   $this->telegram->button('View', 'https://dps.psx.com.pk/download/image/'.$record->image);
		
		$this->notify(new Telegram());	
		dump("Sent pro telegram of id=".$record->id." to ".$to);
	}
	public function toObject($title,$cols)
	{
		$record =  new \StdClass();
		$record->title = $title;
		$code  = $this->notice_types[$title];
		if($code == 'C')
		{
			$record->date = Carbon::createFromFormat('M d, Y', $cols->item(0)->nodeValue)->format('Y-m-d');
			$record->time = str_replace(":","-",$cols->item(1)->nodeValue); 
			$record->symbol = $cols->item(2)->nodeValue; 
			$record->company = $cols->item(3)->nodeValue;
			$record->notification = $cols->item(4)->nodeValue;
			$links = $cols->item(5)->getElementsByTagName('a'); 
			foreach($links as $link)
			{
				$label = $link->nodeValue;
				if($label == 'PDF')
					$record->pdf = $link->getAttribute('href');
				else
					$record->image= $link->getAttribute('data-images');
			}	
			$record->id = md5(
				$record->date.
				$record->time.
				$record->symbol.
				$record->company.
				$record->notification);	
		}
		else
		{
			$record->title = $title;
			$record->date = Carbon::createFromFormat('M d, Y', $cols->item(0)->nodeValue)->format('Y-m-d');
			$record->time = str_replace(":","-",$cols->item(1)->nodeValue); 
			$record->notification = $cols->item(2)->nodeValue;
			$record->links = [];
			$links = $cols->item(3)->getElementsByTagName('a'); 
			foreach($links as $link)
			{
				$label = $link->nodeValue;
				if($label == 'PDF')
					$record->pdf = $link->getAttribute('href');
				else
					$record->image= $link->getAttribute('data-images');
			}	
			$record->id = md5(
				$record->date.
				$record->time.
				$record->notification);
		}
		return $record;
	}
	public function toBasicMessage($record)
	{
		$message = "*".$record->title."*\n";
		if(isset($record->company))
			$message .= $record->company." (".$record->symbol.")\n";
		$message .= "_".$record->date." ".$record->time."_\n";
		$message .= $record->notification."\n";
		return $message;
	}
	public function toProMessage($record)
	{
		$message = "*".$record->title."*\n";
		if(isset($record->company))
			$message .= $record->company." (".$record->symbol.")\n";
		$message .= "_".$record->date." ".$record->time."_\n";
		$message .= $record->notification."\n";
		return $message;
	}
	public function SendNotification($object)
	{
		$sent = 0;
		$record = $this->ReadRecord($object->id);
		if($record == null)
		{
			$record = $object;
			$record->to = [];
		}
		else
			$record->to = $record->to->jsonSerialize();
		
		
		foreach($this->users_basic as $user)
		{
			if(!in_array($user,$record->to))
			{
				$this->SendBasicTelegram($user,$record);
				$record->to[]=$user;
				$this->SaveRecord($record);
				$sent = 1;
			}
		}
		foreach($this->users_pro as $user)
		{
			if(!in_array($user,$record->to))
			{
				$this->SendProTelegram($user,$record);
				$record->to[]=$user;
				$this->SaveRecord($record);
				$sent = 1;
			}
		}
		return $sent;
	}
	
	
	public function Script()
	{
		$today = $this->CurrentDateTimeObj()->format('Y-m-d');
		$ch = curl_init();
		foreach($this->notice_types as $title=>$code)
		{
			curl_setopt($ch, CURLOPT_URL,"https://dps.psx.com.pk/announcements");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS,
				"type=".$code."&symbol&count=50&offset=0&date_from=".$today."&date_to=".$today);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$server_output = curl_exec ($ch);
			
			$dom = new \DOMDocument();
			$dom->loadHTML($server_output); 
			$dom->preserveWhiteSpace = false; 
			/*** the table by its tag name ***/ 
			$tables = $dom->getElementsByTagName('table'); 
			$rows = $tables->item(0)->getElementsByTagName('tr'); 
			foreach ($rows as $row) 
			{
				$cols = $row->getElementsByTagName('td'); 
				if(count($cols)>0)
				{
					$object = $this->toObject($title,$cols);
					if($this->SendNotification($object))
					{
						if($this->options['rebuild']==0)
						{
							$this->Save([$this->scriptname.'_alldone'=>0]);
							curl_close ($ch);
							dump("Partial");
							return;
						}
					}
				}
			}
		}
		curl_close ($ch);
		$this->Save([$this->scriptname.'_alldone'=>1]);
		dump("All");
	}
}