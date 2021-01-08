<?php
namespace App\Apps\Psx;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Psx\Announcement;

class Psxannouncement extends App{
	public $timezone='Asia/Karachi';
	public $scriptname = 'psxannouncement';
	public $options = 0;
	public $to = ["-1001270525449",'@psx_announcements_trial'];
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
	function ReadAnnouncement($id)
	{
		$query=['id'=>$id];
		$obj = $this->db->announcement->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function SaveAnnouncement($announcement)
	{
		$query=['id'=>$announcement->id];
		$options=['upsert'=>true];
		$this->db->announcement->updateOne($query,['$set'=>$announcement],$options);
	}
	public function TimeToRun($update_every_xmin=5)
	{
		$alldone = $this->Read('alldone');
		if($alldone)
			$update_every_xmin = 5;
		else
			$update_every_xmin = 1;
		
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		//$this->db->cards->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	public function ParsePSX_CDC_SECP_NCCPL_Notice($title,$rows)
	{
		$i = 0;
		foreach ($rows as $row) 
		{
			/*** get each column by tag name ***/ 
			$cols = $row->getElementsByTagName('td'); 
			if(count($cols)>0)
			{
				/*** echo the values ***/ 
				$data = new \StdClass();
				$data->title = $title;
				$data->date = $cols->item(0)->nodeValue; 
				$d = Carbon::createFromFormat('M d, Y', $data->date);
				$data->date = $d->format('Y-m-d');
				$data->time = str_replace(":","-",$cols->item(1)->nodeValue); 
				$data->notification = $cols->item(2)->nodeValue;
				$links = $cols->item(3)->getElementsByTagName('a'); 
				$data->pdfurl = null;
				$data->imageurl = null;
				foreach($links as $link)
				{
					$label = $link->nodeValue;
					if($label == 'PDF')
						$data->pdfurl = 'https://dps.psx.com.pk/'.$link->getAttribute('href');
					else
						$data->imageurl = 'https://dps.psx.com.pk/download/image/'.$link->getAttribute('data-images');
				}
				
				$announcement = new Announcement($data);
				$sent=0;
				foreach($this->to as $t)
				{
					if(strpos($t,'trial')!=false)
					{
						$data->pdfurl = null;
						$data->imageurl = 'https://mahmad2504.github.io/psx/subscribe.html';
					}
					$data->id = md5($announcement->message.$data->pdfurl.$data->imageurl.$t);
					$obj = $this->ReadAnnouncement($data->id);
					if($obj == null)
					{
						$announcement->Send($t);
						$this->SaveAnnouncement($data);
						//dump($data);
						$sent=1;
					}
				}
				if($sent==1)
				{
					return 1;
				}
				$i++;
			}				
		}
		return 0;
	}
	
	public function ParseCompanyNotice($title,$rows)
	{
		$i = 0;
		foreach ($rows as $row) 
		{
			/*** get each column by tag name ***/ 
			$cols = $row->getElementsByTagName('td'); 
			if(count($cols)>0)
			{
				/*** echo the values ***/ 
				$data = new \StdClass();
				$data->title = $title;
				$data->date = $cols->item(0)->nodeValue; 
				$d = Carbon::createFromFormat('M d, Y', $data->date);
				$data->date = $d->format('Y-m-d');
				$data->time = str_replace(":","-",$cols->item(1)->nodeValue); 
				$data->symbol = $cols->item(2)->nodeValue; 
				$data->company = $cols->item(3)->nodeValue;
				$data->notification = $cols->item(4)->nodeValue;
				
				$links = $cols->item(5)->getElementsByTagName('a'); 
				$data->pdfurl = null;
				$data->imageurl = null;
				foreach($links as $link)
				{
					$label = $link->nodeValue;
					if($label == 'PDF')
						$data->pdfurl = 'https://dps.psx.com.pk/'.$link->getAttribute('href');
					else
						$data->imageurl = 'https://dps.psx.com.pk/download/image/'.$link->getAttribute('data-images');
				}
				
				$announcement = new Announcement($data);
				$sent=0;
				foreach($this->to as $t)
				{
					if(strpos($t,'trial')!=false)
					{
						$data->pdfurl = null;
						$data->imageurl = 'https://mahmad2504.github.io/psx/subscribe.html';
					}
					$data->id = md5($announcement->message.$data->pdfurl.$data->imageurl.$t);
					$obj = $this->ReadAnnouncement($data->id);
					if($obj == null)
					{
						$announcement->Send($t);
						$this->SaveAnnouncement($data);
						//dump($data);
						$sent=1;
					}
				}
				if($sent==1)
				{
					return 1;
				}
				$i++;
			}				
		}
		return 0;
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
			
			//libxml_use_internal_errors(true);
			$dom = new \DOMDocument();
			$dom->loadHTML($server_output); 
			$dom->preserveWhiteSpace = false; 
			/*** the table by its tag name ***/ 
			$tables = $dom->getElementsByTagName('table'); 
			$rows = $tables->item(0)->getElementsByTagName('tr'); 
			switch($code)
			{
				case 'C':
					if($this->ParseCompanyNotice($title,$rows))
					{
						$this->Save(['alldone'=>0]);
						curl_close ($ch);
						return;
					}
					break;
				case 'E':
				case 'A':
				case 'B':
				case 'D':
					if($this->ParsePSX_CDC_SECP_NCCPL_Notice($title,$rows))
					{
						$this->Save(['alldone'=>0]);
						curl_close ($ch);
						return;
					}
					break;
			}
		}
		curl_close ($ch);
		$this->Save(['alldone'=>1]);
		dump("All done");
	}
}