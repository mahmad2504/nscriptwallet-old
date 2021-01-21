<?php
namespace App\Apps\Sprintcalendar;
use App\Apps\App;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use App\Email;
class Sprintcalendar extends App
{	
	public $options = 0;
	public $timezone='Asia/Karachi';
	public $scriptname = "sprintcalendar";
	public $csprint_no=null;
	public $to=[
		'mumtaz_ahmad@mentor.com',
		'Khula_Azmi@mentor.com',
		'Noor_Ahsan@mentor.com',
		'Bikram_Bhola@mentor.com',
		'Fakhir_Ansari@mentor.com',
		'mohamed_hussein@mentor.com',
		'Cedric_Hombourger@mentor.com',
		'Waqar_Humayun@mentor.com',
		'Srikanth_Krishnakar@mentor.com',
		'Atif_Raza@mentor.com',
		'Unika_Laset@mentor.com',
		'Mona_Desouky@mentor.com',
		'Rizwan_Rasheed@mentor.com',
		'Muhammad_Shafique@mentor.com',
		'MuhammadAwais_Anwar@mentor.com',
	];
	
	
	function __construct($start=null,$end=null,$options=null)
	{
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		
		if($start == null)
		{
			$start = Carbon::now();
			$start->subDays(4);
		}
		if($end == null)
		{
			$end = Carbon::now();
			$end=  $end->addDays(50);
		}
		
		$base='2019-12-30';	
		$sprint_number = 1;
		
		$base = Carbon::parse($base)->startOfWeek();
		$start = Carbon::parse($start)->startOfWeek();
		if($start < $base)
			$start = $base;
	
		$diff= $start->diffInDays($base);
		
		$end = Carbon::parse($end)->endOfWeek(); 
		$years=[];
		$months=[];
		$weeks=[];
		$sprints=[];
		$days=[];
		$period = new CarbonPeriod($start, '1 day', $end);
		$i=$diff;
		$last_sprint_year = null;
		foreach ($period as $key => $date) 
		{
			//format('M d Y');
			//echo $key.' '.$date."<br>";
			//dd($date->format('Y'));
			$year=$date->format('Y');
			$month=$date->format('m');
			$day=$date->format('d');
			$week=$date->isoWeek();
			$wyear=$date->isoWeekYear();
			$today=$date->IsToday()?1:0;
			//dump($date->format('Y-m-d'));
			//dump($today);
			$sprint_number=floor($i/21)+1;
			
			
			
			if(!isset($years[$year]))
				$years[$year]=[];
			
			if(!isset($months[$year."_".$month]))
				$months[$year."_".$month]=[];
			
			if(!isset($weeks[$wyear."_".$week]))
				$weeks[$wyear."_".$week]=[];
			
			//if(!isset($weeks[$month]))
			//	$months[$year."_".$month]=[];
			$sprint_year=$year;
			if($last_sprint_year != null)
			{
				if(isset($sprints[$last_sprint_year."_".$sprint_number]))
				{
					if(count($sprints[$last_sprint_year."_".$sprint_number])<=21)
						$sprint_year = $last_sprint_year;
				}
			}
			if(!isset($sprints[$sprint_year."_".$sprint_number]))
			{
				// we are starting new sprint_number
				if($last_sprint_year != null)
				{
					if($last_sprint_year != $sprint_year) // and we are new boundary
					{
						$sprint_number = 1;
						$i=0;
					}
				}
			}
			if(!isset($sprints[$sprint_year."_".$sprint_number]))
			{
				$sprints[$sprint_year."_".$sprint_number]=[];
			
			}
			
			$years[$year][] = $today;
			$months[$year."_".$month][] = $today;
			$weeks[$wyear."_".$week][] = $today;
			$obj=new \StdClass();
			$obj->date = $date->format('Y-m-d');
			$obj->today = $today;
			$sprints[$sprint_year."_".$sprint_number][]=$obj;
			
			
			$last_sprint_year = $sprint_year; 
			
			if($today == 1)
				$this->csprint_no = $sprint_year."_".$sprint_number;
			
			//dump($sprint_number);
				//$this->currentsprint=$sprints[$sprint_number];
			
			$days[$date->format('Y-m-d')]=$today;
			
			$i++;
			//$sprint_number=$i/21;
			//if($i%21==0)
			//	$sprint_number++;
		}
		$this->years=$years;
		$this->months=$months;
		$this->weeks=$weeks;
		$this->days=$days;
		$this->sprints=$sprints;
		
		parent::__construct($this);
	}
	public function TimeToRun($update_every_xmin=120)
	{
		$now = Carbon::now($this->timezone);
		$lastemailsenton = $this->app->Read('lastemailsenton');
		if(($now->format('Y-m-d') == $lastemailsenton)&&($this->options['email_resend']==0))
		{
			dump("Email already sent today");
			return false;
		}
		if($now->format('H')<9)
			return false;
		return parent::TimeToRun($update_every_xmin);
	}
	public function Email($sprint,$start)
	{
		$email = new Email();
		if($start)
			$subject = 'Notification Sprint '.$sprint.': Starts today';
		else
			$subject = 'Notification Sprint '.$sprint.' Close today';	
		$msg = $subject.'<br>';
		$msg .= '<br>';
		$msg .= '<br>';
		$msg .= "<small>This is an auto generated notification so please donot reply to this email<br>";
		$msg .= "If you are not interested in these notifications, please send an email to mumtaz_ahmad@mentor.com".'<br>';
		$msg .= '<br>';
		$msg .= "For complete sprint calender please <a href='http://scripts.pkl.mentorg.com/sprintcalendar'>click here</a></small><br>";
		$email->Send($this->options['email'],$subject,$msg,$this->to,[]);
	}
	public function Script()
	{
		dump("Running script");
		$now = Carbon::now($this->timezone); 
		
		$start = Carbon::now();
		$start->subDays(63);
		$end = Carbon::now();
		$end=  $end->addDays(300);
		
		
		//ob_start('ob_gzhandler');

		$calendar =  new SprintCalendar($start,$end);
		$tabledata = $calendar->GetGridData();
		$this->csprint_no = null;
		foreach($tabledata->sprints as $sprint_name=>$data)
		{
			foreach($data as $d)
			{
				if($d->today)
				{
					$this->csprint_no = $sprint_name;
					break;
				}
			}
			if($this->csprint_no != null)
				break;
		}
		$sstart = Carbon::parse($tabledata->sprints[$this->csprint_no][0]->date);
		$send = Carbon::parse(end($tabledata->sprints[$this->csprint_no])->date);
		$send->subDays(2);///since sprint closes on friday and now sunday
		$sprint = $this->csprint_no;
			
		if($sstart->IsToday())
		{
			$this->Email($sprint,1);
			$lastemailsenton = $this->app->Save(['lastemailsenton'=>$now->format('Y-m-d')]);
			echo "Sent email eminder for start of sprint ".$sprint;
		}
		if($send->IsToday())
		{
			$this->Email($sprint,0);
			$lastemailsenton = $this->app->Save(['lastemailsenton'=>$now->format('Y-m-d')]);
			echo "Sent email eminder for closure of sprint ".$sprint;

		}
	}
	public function GetGridData()
	{
		$data = new \StdClass();
		$data->years=$this->years;
		$data->months=$this->months;
		$data->weeks=$this->weeks;
		$data->days=$this->days;
		$data->sprints=$this->sprints;
		return $data;
	}
	public function GetCurrentSprint()
	{
		return $this->csprint_no;
	}
	public function GetCurrentSprintStart()
	{
		return $this->sprints[$this->csprint_no][0];
	}
	public function GetCurrentSprintEnd()
	{
		return end($this->sprints[$this->csprint_no]);
	}
}