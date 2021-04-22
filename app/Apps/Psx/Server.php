<?php
namespace App\Apps\Psx;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Notifications\Notifiable;
use App\Notifications\Telegram;
use NotificationChannels\Telegram\TelegramMessage;

class Server extends Psx{
	use Notifiable;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function InConsole($yes)
	{
		if($yes)
		{
			$this->datafolder = "data/psx/dailydata";
		}
		else
			$this->datafolder = "../data/psx/dailydata";
	}
	public function TimeToRun($update_every_xmin=60)
	{
		$scriptname = $this->scriptname;
		$update_every_xmin = 1;
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		$this->options['email']=0;// no emails when rebuild
	}
	public function Script()
	{
		https://api.telegram.org/bot1580993378:AAFb7C5JzZqF6DnRxho2sB-kdmoaAyqzONE
		
		$this->telegram = TelegramMessage::create()
        ->to('-1001218166281')
        ->content('Hello'); 
		$this->notify(new Telegram());	
	}
}