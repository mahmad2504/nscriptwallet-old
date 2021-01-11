<?php
namespace App\Apps\Psx;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;

use Illuminate\Notifications\Notifiable;
use App\Notifications\Telegram;
use NotificationChannels\Telegram\TelegramMessage;

class Bookclosure extends Psx{
	use Notifiable;
	public $scriptname = 'psx:bookclosure';
	public $options = 0;
	public $url = "https://www.ksestocks.com/BookClosures";
	
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
		if($this->options['email'] == 0)
		{
			dump($this->toBasicMessage($record));
			return;
		}
		$this->telegram = TelegramMessage::create()
        ->to($to)
        ->content($this->toBasicMessage($record));
		$this->notify(new Telegram());	
		dump("Sent basic telegram of id=".$record->id." to ".$to);
	}
	public function SendProTelegram($to,$record)
	{
		if($this->options['email'] != 1)
		{
			dump($this->toProMessage($record));
			return;
		}
		$this->telegram = TelegramMessage::create()
        ->to($to)
        ->content($this->toProMessage($record));  
		$this->notify(new Telegram());	
		dump("Sent pro telegram of id=".$record->id." to ".$to);
	}
	public function toObject($record)
	{
		$record->id = md5($record->symbol.$record->cname.$record->faceval.$record->bcfrom.$record->bcto.$record->payout.$record->status);	
		return $record;
	}
	public function toBasicMessage($record)
	{
		$message = "*".'Book Closure'."*\n";
		$message .= $record->cname."(".$record->symbol.")\n";
		$message .= "Details are available in Pro version only";
		return $message;
	}
	public function toProMessage($record)
	{
		$message = "*".'Book Closure'."*\n";
		$message .= $record->cname."(".$record->symbol.")\n";
		$message .= "Face value=".$record->faceval."\n";
		$message .= "Book Closing=".$record->bcfrom."\n";
		$message .= $record->status."\n";
		return $message;
	}
	public function SendNotification($object,$status)
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
		curl_setopt($ch, CURLOPT_URL,$this->url);
		//curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/txt'));
		//curl_setopt($ch, CURLOPT_POSTFIELDS,'dtype=byday&sdate=2021-01-06&rfdate=2020-01-09&rtdate=2021-01-07&mansear');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec ($ch);
		
		$json = explode("var bcs=",$server_output)[1];
		$json = explode("}]};",$json)[0];
		$json = $json."}]}";
		$data = json_decode($json);
		foreach($data->cur as $object)
		{
			$status=null;
			$bsfrom = new Carbon($object->bcfrom);
			$bsfrom_minustwo = $bsfrom->subDays(2);
			if($bsfrom_minustwo->isToday())
				$status="Share is Ex today (".$bsfrom_minustwo->format('Y-m-d').")";
			$bsfrom = new Carbon($object->bcfrom);
			$bsfrom_minusthree = $bsfrom->subDays(3);
			if($bsfrom_minusthree->isToday())
				$status="Share will Ex on ".$bsfrom_minusthree->format('Y-m-d')."(tomorrow)";
			
			if($status != null)
			{
				$object->status=$status;
				$object = $this->toObject($object);
				if($this->SendNotification($object,$status));
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
		curl_close ($ch);
		$this->Save([$this->scriptname.'_alldone'=>1]);
		dump("All");
	}
}