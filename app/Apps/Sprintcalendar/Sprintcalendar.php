<?php
namespace App\Apps\Sprintcalendar;
use App\Apps\App;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class Sprintcalendar extends App{
	public $timezone='Asia/Karachi';
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
	public function Permission($update_every_xmin=0)
	{
		return parent::Permission($update_every_xmin);
	}
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			case 'default':
				dd("Implement IssueParser");
		}
	}
	function __construct($start,$end)
	{
		$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		date_default_timezone_set($this->timezone);
		
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
		parent::__construct(__NAMESPACE__, $server);
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