<?php
namespace App\Apps\Psx;
use Illuminate\Notifications\Notifiable;
use App\Notifications\Telegram;
use NotificationChannels\Telegram\TelegramMessage;
class Announcement{
	use Notifiable;
	public function __construct($data)
	{
		$this->data = $data;
		$this->message = "*".$data->title."*\n";
		if(isset($data->company))
			$this->message .= $data->company." (".$data->symbol.")\n";
		$this->message .= "_".$data->date." ".$data->time."_\n";
		$this->message .= $data->notification."\n";
		
		
		//$this->message .= '[inline URL](http://www.example.com/)';
		//$this->message .= '*bold text*';
		//$this->message .= 'AAAA ` enlight piece ` BBB';
	}
   
	public function Send($to)
	{
		$this->telegram = TelegramMessage::create()
        ->to($to)
        ->content($this->message);
			
		if($this->data->pdfurl != null)
		   $this->telegram->button('Pdf', $this->data->pdfurl);
		if($this->data->imageurl != null)
		   $this->telegram->button('View', $this->data->imageurl);
	  
		$this->notify(new Telegram());	
		dump("Sent telegram id=".$this->data->id." to ".$to);
	}
}