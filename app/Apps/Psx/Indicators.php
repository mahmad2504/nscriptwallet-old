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



class Indicators extends Psx{
	use Notifiable;
	private $datafolder = null; // will set in constructor
	public $scriptname = 'psx:rsi';
	public $company_data = [];
	public $options = 0;
	public $url_day = "https://dps.psx.com.pk/timeseries/int/";
	public $url_history = "https://dps.psx.com.pk/timeseries/eod/";
	public $holidays = [
	"2020-03-23",
	"2020-05-01",
	"2020-05-22",
	"2020-05-25",
	"2020-05-26",
	"2020-05-27",
	"2020-07-31",
	"2020-08-14",
	"2020-10-30",
	"2020-12-25",
	"2021-02-05"];
	public $scripts = [
	/*'automobile' => ['AGTL','ATLH','DFML','GHNL','GHNI','GAIL','HINO','HCAR','INDU','MTL','PSMC','SAZEW','MTL'],
	'automobileparts' => ['AGIL','ATBA','DWAE','EXIDE','GTYR','LOADS','THALL'],
	'electric' => ['EMCO','JOPP','PAEL','PCAL','SIEM','WAVES'],
	'cement'=>['ACPL','BWCL','CHCC','DBCI','DCL','DGKC','DNCC','FCCL','FECTC','FLYNG','GWLC','JVDC','KOHC','LPCL','LUCK',
			   'MLCF','PIOC','POWER','SMCPL','THCCL'],
	'chemical'=>['AGL','ARPL','BAPL','BERG','BIFO','BUXL','COLG','DAAG','DOL','DYNO','EPCL','GGL','ICI','LOTCHEM','LPGL',
	'NICL','NRSL','PAKOXY','PPVC','SARC','SHCI','SITC','SPL','WAHN'],
	'banks'=>['ABL','AKBL','BAFL','BAHL','BIPL','BOK','BOP','FABL','HBL','HMB','JSBL','MCB','MEBL','NBP','SBL','SCBPL','SILK',
	'SMBL','SNBL','UBL'],
	'engineering'=>['ADOS','AGHA','ASL','ASTL','BCL','CSAP','DADX','DKL','DSL','HSPI','INIL','ISL','ITTEFAQ','KSBP',
	'MSCL','MUGHAL','PECO','QUSW'],
	'Fertilizer'=>['AHCL','EFERT','ENGRO','FATIMA','FFBL','FFC'],
	'Food and care'=>['ASC','CLOV','FCEPL','FFL','GIL','GLPL','ISIL','MFFL','MFL','MUREB','NATF','NESTLE','NMFL','PREMA',
	'QUICE','RMPL','SCL','SHEZ','TOMCL','TREET','UPFL','ZIL'],
	'Glass & ceramics'=>['BGL','FRCL','GGGL','GHGL','GVGL','KCL','REGAL','STCL','TGL'],
	'Insurance'=>['AGIC','AICL','ALAC','ASIC','ATIL','BIIC','CENI','CSIL','EFUG','EFUL','EWIC','HICL','IGIHL','IGIL',
	'JGICL','JLICL','PAKRI','PIL','PINL','PKGI','PRIC','RICL','SHNI','SICL','SSIC','TPLI','UNIC','UVIC'],
	'Inv banks'=>['786','AHL','AMBL','AMSL','BIPLS','CYAN','DAWH','DEL','EFGH','ESBL','FCEL','FCIBL','FCSC',
	'FDIBL','FNEL','ICIBL','JSCL','JSGCL','JSIL','MCBAH','NEXT','PASL','PDGH','PRIB','PSX','SIBL','TRIBL',
	'TSBL'],
	'Jute'=>['CJPL','SUHJ'],
	'Leasing'=>['CPAL','ENGL','GRYL','OLPL','PGLC','PICL','SLCL','SLL','SPLC'],
	'Leather'=>['BATA','FIL','LEUL','PAKL','SRVI'],
	'Mis'=>['AKDCL','AKGL','ARPAK','DCTL','DIIL','ECOP','GAMON','GOC','HACC','HADC','MACFL','MWMP','OML','PACE','PHDL',
	'PSEL','STPL','TPLP','TRIPF','UBDL','UDPL']*/
	'kse-100'=>["ANL","HASCOL","UNITY","TRG","MLCF","KEL","PIBTL","PAEL","BOP","FCCL","BYCO","LOTCHEM","DGKC","FFBL","KAPCO","ANL", 
"PPL","PTC","HUBC","EPCL","PIOC","OGDC","SNGP","ISL","EFERT","ATRL","NBP","HBL","BAFL",	
"PSO","SSGC","UBL","NML","CHCC","FABL","GATM","SEARL","MEBL","FFC","LUCK","INIL","PSX","AKBL",
"NCL","ILP","ENGRO","BAHL","MCB","PSMC","AGP","AICL","POL",  
"DCR","HCAR","KOHC","FATIMA","SHEL","FCEPL","SYS","HMB","KTML","GHGL","OLPL",
"SPWL","GLAXO","DAWH","IGIHL","SCBPL","PKGS","ABL","APL","NATF","GSKCH","FML","THALL",	
"MARI","ABOT","ICI","FHAM","SHFA","MTL","JLICL","INDU","BNWM","AGIL","HINOON","SRVI",
"ARPL","MUREB","EFUG","ATLH","JDWS","STJT","NESTLE","GATI","COLG","IDYM","PAKT","PMPK","GHNI","GHNL"]
	];

	
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
	function truncate($val, $f="0")
	{
		if(($p = strpos($val, '.')) !== false) {
			$val = floatval(substr($val, 0, $p + 1 + $f));
		}
		return $val;
	}
	public function Download($url,$filename)
	{
		if(!file_exists($this->datafolder))
			mkdir($this->datafolder, 0, true);
		$filename = $this->datafolder."/".$filename;
	
		$file = fopen($filename, "w");
		$ch_start = curl_init();
		curl_setopt($ch_start, CURLOPT_URL, $url);
		curl_setopt($ch_start, CURLOPT_FAILONERROR, true);
		curl_setopt($ch_start, CURLOPT_HEADER, 0);
		curl_setopt($ch_start, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch_start, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch_start, CURLOPT_BINARYTRANSFER,true);
		curl_setopt($ch_start, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch_start, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch_start, CURLOPT_SSL_VERIFYPEER, 0); 
		curl_setopt($ch_start, CURLOPT_FILE, $file );
		$page = curl_exec($ch_start);
		curl_close($ch_start);
		fclose($file);
		if(file_exists($filename))
			return $filename;
		return null;
	}
	public function Unzip($zipfile)
	{
		$basename = basename($zipfile,".zip");
		$za = new \ZipArchive; 
		$za->open($zipfile);
		for ($i=0; $i<$za->numFiles;$i++) 
		{
			$za->extractTo($this->datafolder);
			$extractedfilename = $za->getNameIndex($i);
			rename($this->datafolder."/".$extractedfilename,$this->datafolder."/".$basename.".txt") ;
			return $this->datafolder."/".$basename.".txt";
		}
	}
	public function GetPastData($symbol)
	{
		$ch = curl_init();
		$url = $this->url_history.$symbol;
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($ch);
		$data = json_decode($output);
		$data = $data->data;
		$average_volume=0;
		$result =  new \StdClass();
		$i=0;
		foreach($data as $d)
		{
			$dates[] = $d[0]; 
			$closing [] = bcdiv($d[1],1,2);
			$volumes [] = $d[2];
			$average_volume += $d[2];
			$i++;
			if($i==100)
				break;
		}
		foreach($dates as $date)
		{
			$dt = $dt->SetTimeStamp($date->format('Y-m-d'));
			dump($dt);
		}
	
		$result->average_volume = $average_volume/count($volumes);
		$result->closing = array_reverse($closing);
		
		$prev_closing = null;
		foreach($result->closing as $price)
		{
			if($prev_closing != null)
			{
				$result->change[] =  round($price-$prev_closing,2);
			}
			else
				$result->change[] = 0;
			$prev_closing=$price;
		}
		$result->volumes = array_reverse($volumes);
		$result->dates = array_reverse($dates);
		$dt =  new Carbon();
		$dt->SetTimeStamp($dates[0]);
		$result->latest = $dt->SetTimeStamp($dates[0])->format('Y-m-d');
		return $result;
	}
	public function GetDayData($symbol)
	{
		$ch = curl_init();
		$url = $this->url_day.$symbol;
		curl_setopt($ch, CURLOPT_URL,$url);
		//curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
		//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/txt'));
		//curl_setopt($ch, CURLOPT_POSTFIELDS,'dtype=byday&sdate=2021-01-06&rfdate=2020-01-09&rtdate=2021-01-07&mansear');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec ($ch);
		$data = json_decode($output);
		if(!isset($data->data))
			return null;
		$data = $data->data;
		
		$volume = 0;
		$result =  new \StdClass();
		$min = 10000000;
		$max = 0;
		if(count($data) > 0)
		{
			$trade=$data[0];
			foreach($data as $d)
			{
				$time[] = $d[0]; 
				if($d[1] < $min)
					$min = $d[1];
				if($d[1] > $max)
					$max = $d[1];
				$price [] = $d[1];
				$quantity [] = $d[2];
				$volume += $d[2];
			}
		}
		else
			return null;
		
		$dt =  new Carbon();
		$dt->SetTimeStamp($time[0]);
		$result->date = $dt->SetTimeStamp($time[0])->format('Y-m-d');
		$result->cur = $price[0];
		$result->time = array_reverse($time);
		$result->price = array_reverse($price);
		$result->quantity = array_reverse($quantity);
		$result->volume = $volume;
		$result->min = $min;
		$result->max = $max;
		dump($result->min);
		dd($result->max);
		return $result;
	}
	public function ColorToCode($col)
	{
		$green = "\e[0;31;32m";
		$red = "\e[0;31;31m";
		$yellow = "\e[0;31;93m";
		$orange = "\e[0;31;91m";
		$nocol = "\e[0;31;39m";
		if($col == $green)
			return 4;
		if($col == $yellow)
			return 3;
		if($col == $orange)
			return 2;
		if($col == $red)
			return 1;
	}
	public function EMAIndicator($symbol,$data,$average_volume)
	{
		$today = new Carbon();
		$today = $today->format('Y-m-d');
		$green = "\e[0;31;32m";
		$red = "\e[0;31;31m";
		$yellow = "\e[0;31;93m";
		$orange = "\e[0;31;91m";
		$nocol = "\e[0;31;39m";
		$lightgreen = "\e[0;31;92m";
		$rsi = trader_rsi ( $data->closing,14);
		$ema25 = trader_ema ($data->closing ,25 );
		$ema10 = trader_ema ($data->closing ,10 );
		$macd = trader_macd ($data->closing, 12 , 26 , 9 );
		$macd = $macd[2];
		$pmacd  = null;
		$pprice_col = null;
		$pmomentum=0;
		$nmomentum=0;
		$reversal='';
		$signal_bearish=0;
		$signal_bullish=0;
		$signal = '';
		$percent_volume=0;
		foreach($macd as $i=>$macd_)
		{
			$volume = $data->volumes[$i];
			$ema25_ = $ema25[$i];
			$ema10_ = $ema10[$i];
			$closing = $data->closing[$i];
			$dt =  new Carbon();
			$date = $dt->SetTimeStamp($data->dates[$i])->format('Y-m-d');
			
			$closing = $data->closing[$i];
			$change = $data->change[$i];	
			
			$gap = $ema10_ - $ema25_;
			
			if($change >= 0)
				$change_col = $green;
			else
				$change_col = $red;
			if($ema10_>$ema25_)
			{
				$ema_col = $green;
			}
			else
			{
				$ema_col = $yellow;	
			}
			
			if(($closing < $ema10_)&&($closing < $ema25_))
			{
				$price_col = $red;
				
			}
			else if(($closing > $ema10_)&&($closing < $ema25_))
				$price_col = $orange;
			else if(($closing > $ema25_)&&($closing < $ema10_))
				$price_col = $yellow;
			else
				$price_col = $green;
			
			if($macd_ < 0)
				$macd_col = $orange;
			else if($macd_ < 0)
				$macd_col = $nocol;
			else
				$macd_col = $green;
			
			
			if($pmacd<$macd_)
			{
				$pmomentum++;
				$nmomentum=0;
			}
			else if($pmacd>$macd_)
			{
				$nmomentum++;
				$pmomentum=0;
			}
			$reversal='';
			$reversal_col=$nocol;
			if($nmomentum == 3)
			{
				$nmomentum = 0;
				$signal_bearish++;
				$signal_bullish=0;
				$reversal='bearish('.$signal_bearish.')';
				$reversal_col = $orange;
			}
			if($pmomentum == 3)
			{
				$pmomentum = 0;
				$signal_bullish++;
				$signal_bearish=0;
				$reversal='bullish('.$signal_bullish.')';
				$reversal_col = $green;
			}
			$percent_volume = $volume/$average_volume*100;
			if($percent_volume < 50)
				$volume_col = $yellow;
			else if($percent_volume < 100)
				$volume_col = $green;
			else
				$volume_col=$lightgreen;
			
			//if($volume < $average_volume)
			//{
			//	$volume_col = $yellow;
			//}
			//else
			//	$volume_col = $green;
			$rsi_ = $rsi[$i];
			if($rsi_ > 70)
				$rsi_col = $red;
			else if($rsi_ > 50)
				$rsi_col = $yellow;
			else
				$rsi_col = $lightgreen;
			
			$format = "%7s %4s %10s ".$price_col." %6.2f ".$change_col." %6.2f ".$nocol."ema[".$ema_col."%6.2f ".$ema_col."%6.2f".$nocol."] macd=".$macd_col."%6.2f ".$reversal_col.$reversal." vol=".$volume_col." %d(%4.2f)".$rsi_col." rsi=%2.1f\n".$nocol;
			$signal = '';
			if($pprice_col != null)
			{
				$pprice_code = $this->ColorToCode($pprice_col);
				$price_code = $this->ColorToCode($price_col);
				if($pprice_code != $price_code)
				{
					if($pprice_code > $price_code)
						 $signal = 'Sell';
					else
						 $signal = 'Buy';
					
					//if($today == $date)
					//	echo sprintf($format, $signal,$date,$closing,$change,$ema10_,$ema25_,$macd_,$volume);
				}
			}
			//$format = "%10s ".$price_col." %5.2f \033[0m ema10=%5.2f ema20=%5.2f\n";
			
			//dd($format);
			
			$pmacd = $macd_;
			$pprice_col=$price_col;
			//dump($date.' '.$data->closing[$i].' [ema10='.$ema10_."  ema25=".$ema25_."]  emagap=".$gap." macd=".$macd[$i]." change=".$change);
		
		
			//if($gap < 0 )// ema25 is above and ema10 is below
			//	dump($date.' '.$closing.' bearish [ema10='.$ema10_."  ema25=".$ema25_."]  emagap=".$gap." macd=".$macd." change=".$change);
			//else // ema10 is above and ema25 is below
			//	dump($date.' '.$closing.' bullish '.$gap." ".$change);
			
			/*if($change > 0)
			{
				if(($closing > $ema25_) && ($closing > $ema10_))
					$data->emaindicator[$i]=3;
				elseif(($closing > $ema25_) && ($closing < $ema10_))
					$data->emaindicator[$i]=2;
				elseif(($closing < $ema25_) && ($closing < $ema10_))
					$data->emaindicator[$i]=1;
			}
			else
			{
				if(($closing > $ema25_) && ($closing > $ema10_))
					$data->emaindicator[$i]=-3;
				elseif(($closing > $ema25_) && ($closing < $ema10_))
					$data->emaindicator[$i]=-2;
				elseif(($closing < $ema25_) && ($closing < $ema10_))
					$data->emaindicator[$i]=-1;
			}*/
			//dump($i."  ".$val."  ".$data->closing[$i]." ".$data->change[$i]);
		}
		echo sprintf($format,$symbol,$signal,$date,$closing,$change,$ema10_,$ema25_,$macd_,$volume,$percent_volume,$rsi_);
		//dd($data->emaindicator);
		//if($closing[$size-2]-
		//dump(end($closing)." ".end($ema25)."  ".end($ema10));
	}
	public function DailyDataObjects()
	{
		$output = [];
		$period = CarbonPeriod::create(Carbon::now()->subDays(360),Carbon::now());
		
		foreach ($period as $date) 
		{
			//$dt =  $date->format('Y-m-d');
			if(in_array($date->format('Y-m-d'),$this->holidays))
				continue;
			if(!$date->isWeekend())
			{
				$obj =  new \StdClass();
				$obj->date = $date->format('Y-m-d');
				if(file_exists($this->datafolder."/".$obj->date.".txt"))
					$obj->data_file = $this->datafolder."/".$obj->date.".txt";
				$output[] = $obj;
			}	
		}
		return $output;
	}
	public function ParseDailyData($object)
	{
		$row = 1;
		if (($handle = fopen($object->data_file, "r")) !== FALSE) 
		{
			while (($data = fgetcsv($handle, 1000, "|")) !== FALSE) 
			{
				$symbol = $data[1];
				if(count(explode("-",$symbol))==2)
					continue;
				if(isset($this->company_data[$symbol]))
					$company = $this->company_data[$symbol];
				else
				{
					$company = new \StdClass();
					$company->symbol=$symbol;
					$company->sector=$data[2];
					$company->name=$data[3];
					$company->data = [];
					$company->avg_vol = 0;
					$this->company_data[$symbol] = $company;
				}
				$obj = new \StdClass();
				$obj->date=$object->date;
				$obj->open=$data[4];
				$obj->high=$data[5];
				$obj->low=$data[6];
				$obj->close=$data[7];
				
				
				$obj->vol=$data[8];
				$obj->change=($obj->close)-$data[9];
				
				$company->data[]=$obj;
				$company->avg_vol += $obj->vol;
				$company->avg_vol = round($company->avg_vol/2);
				//30.80|31.50|29.80|31.05|9169000|30.61|||
			}
			
		}
		fclose($handle);
		return $this->company_data;
	}
	
	public function Populate($objects)
	{
		$i = 0;
		$del = [];
		foreach($objects as $object)
		{
			//
			if(!isset($object->data_file))
			{
				dump("Downloading data for ".$object->date);
				$downloaded_zip_file_name = $this->Download('https://dps.psx.com.pk/download/mkt_summary/'.$object->date.'.Z',$object->date.".zip");
				if($downloaded_zip_file_name != null)
				{
					$object->data_file = $this->Unzip($downloaded_zip_file_name);
				}
			}
			if(isset($object->data_file))
				$this->ParseDailyData($object);
			else
			{
				$del[] = $i;
				dump("Not available");
			}
			$i++;
		}
		foreach($del as $d)
			unset($objects[$d]);
		return $this->company_data;
	}
	public function GetData($symbol)
	{
		$result =  new \StdClass();
		$company = $this->company_data[$symbol];
		foreach($company->data as $data)
		{
			dd($data);
			$result->open[] = $data->open;
			$result->high[] = $data->high;
			$result->low[] = $data->low;
			$result->close[] = $data->close;
			$result->vol[] = $data->vol;
			$result->change[] = $data->change;
			$result->date[] = $data->date;
			$result->company = $company ;
		}
		return $result;
	}
	public function rsi14($data)
	{
		//$rsi = trader_rsi ($closing,14);
		
		$data->rsi = trader_rsi ($data->close,14);
		$ema = trader_ma ($data->rsi, 10);
		$ema_index = 9;
		$prev = -1;
		$prev_ema = -1;
		$i=1;
		foreach($data->rsi as $key=>$value)
		{
			unset($data->rsi[$key]);
			if($i==9)
			{
				break;
			}
			$i++;
		}
		
		foreach($data->rsi as $key=>$value)
		{
			if($prev == -1)
			{
				$prev = $value;
				$prev_ema = $ema[$ema_index];
				$ema_index++;
				continue;
			}
			$cur_ema = $ema[$ema_index];
			
			if(($value-$prev) < 0)
				$dir = 'down';
			else if(($value-$prev) == 0)
				$dir = 'none';
			else
				$dir = 'up';
			
			
			if(($cur_ema-$prev_ema) < 0)
				$dir_ema = 'down';
			else if(($cur_ema-$prev_ema) == 0)
				$dir_ema = 'none';
			else
				$dir_ema = 'up';
			if(($dir_ema == 'up')&&($dir=='down'))
				dump($data->date[$key]." "." ".$data->close[$key]." ".$ema[$ema_index]." ".$value." down\n");
			if(($dir_ema == 'down')&&($dir=='up'))
				dump($data->date[$key]." "." ".$data->close[$key]." ".$ema[$ema_index]." ".$value." up\n");
			else
				dump($data->date[$key]." ".$data->close[$key]);
			//dump($ema[$ema_index]." ".$value." ".$dir."  ".$dir_ema."\n");
			$prev = $value;
			$prev_ema = $ema[$ema_index];
			$ema_index++;
			
		}
		dd('');
	}
	public function macd($closing)
	{
		$values = trader_macd($closing, 12 , 26 , 9 );
		$prev = -1;
		$pmomentum = 0;
		$nmomentum = 0;
		$nmacd = 0;
		$pmacd = 0;
		foreach($values[2] as $key=>$value)
		{
			if($value < 0 )
			{
				$nmacd = ($nmacd + $value) /2 ;
				$pmacd=0;
				$values[5][$key]=round($nmacd,2);
				$values[6][$key]=round($pmacd,2);
			}
			if($value > 0 )
			{
				$pmacd = ($pmacd + $value) /2 ;
				$nmacd=0;
				$values[6][$key]=round($pmacd,2);
				$values[5][$key]=round($nmacd,2);
				
			}
			if($pmacd < 0)
				$values[7][$key] = round($pmacd,2);
			else
				$values[7][$key] = round($nmacd,2);
			
			$values[3][$key]='';
			$values[4][$key]='';
			if($prev  == -1)
			{
				$prev = $value;
				continue;
			}
			if(($prev < 0)&& ($value >= 0))
			{
				$values[3][$key]=1;
				$pmomentum = 0;
				$nmomentum = 0;
			}
			if(($prev > 0)&& ($value <= 0))
			{
				$values[3][$key]=-1;
				$pmomentum = 0;
				$nmomentum = 0;
			}
			//dump($key);
			$div[$key] = $value - $prev;
			if($div[$key] < 0)
			{
				$pmomentum = 0;
				$nmomentum++;
			}
			else
			{
				$nmomentum=0;
				$pmomentum++;
			}
			if($pmomentum == 3)
			{
				$pmomentum=0;
				$values[4][$key]=1;
			}
			if($nmomentum == 3)
			{
				$nmomentum=0;
				$values[4][$key]=-1;
			}
			$prev = $value;
		}
		return $values;
	}
	public function Candles($data)
	{
		for($i=0;$i<count($data->close);$i++)
		{
			$date = $data->date[$i];
			$open = $data->open[$i];
			$close = $data->close[$i];
			
			$high = $data->high[$i];
			$low = $data->low[$i];
			$vol = $data->vol[$i];
			if($open == '')
				$open = $close;
			
			
			$body = round($close-$open,2);
			if($body < 0 )  // H - 0---C ---L
			{
				$color = 'red';
				$lshadow =  round($close - $low,2);
				$ushadow =  round($high - $open,2);
			}
			else if($body > 0 ) // H - C---0 ---L
			{
				$color = 'green';
				$lshadow = round($open - $low,2);
				$ushadow =  round($high - $close,2);
			}
			else    //// H - 0C ---L 
			{
				$lshadow = round($close - $low,2);
				$ushadow = round($high - $open,2);
				$color = 'neutral';
			}
			$candle = new \StdClass();
			$candle->lshadow = $lshadow;
			$candle->ushadow = $ushadow;
			$candle->body = $body;
			$candle->color = $color;
			
			$data->candle[]=$candle;
		}
	}
	public function Bullish_OpenPriceIndicator($company)
	{
		for($i=0;$i<count($company->close);$i++)
		{
			$company->bullish->open[$i] = 0;
			$candle = $company->candle[$i];
			$close = $company->close[$i];
			$open = $company->open[$i];
			$vol = $company->vol[$i];
			if($i>0)
			{
				$popen = $company->open[$i-1];
				$pclose = $company->close[$i-1];
				$phigh = $company->high[$i-1];
				$plow = $company->low[$i-1];
				$pvol = $company->vol[$i-1];
				$pcandle = $company->candle[$i-1];
				if(($close > $pclose)||($open > $pclose))
				{
					if($candle->color == 'green')
					{
						if($vol > $company->avg_vol)
						{
							$company->bullish->open['last_index']=$i;
							$company->bullish->open[$i] = 1;
						}
						else if($vol > $pvol)
						{
							$company->bullish->open[$i] = .7;
							$company->bullish->open['last_index']=$i;
						}
						else
						{
							$company->bullish->open[$i] = .5;
							$company->bullish->open['last_index']=$i;
						}
					}
				}
			}
		}
	}
	public function BuildData($company)
	{
		$obj =  new \StdClass();
		$obj->symbol = $company->symbol;
		$obj->sector = $company->sector;
		$obj->name = $company->name;
		$obj->avg_vol = $company->avg_vol;
		
		$i=0;
		foreach($company->data as $data)
		{
			$obj->open[] = $data->open;
			$obj->high[] = $data->high;
			$obj->low[] = $data->low;
			$obj->close[] = $data->close;
			$obj->vol[] = $data->vol;
			$obj->change[] = $data->change;
			$obj->date[] = $data->date;
			$obj->last_index = $i++;
			$obj->last_date = $data->date;
		}
		
		$this->Candles($obj);
		return $obj;
	}
	public function Script()
	{
		
		$dailydata = $this->DailyDataObjects();
		$this->company_data = $this->Populate($dailydata);
		$last_week = Carbon::now()->subDays(7);
		$this->db->companies->drop();
		foreach($this->company_data as $company_data)
		{
			
			if($company_data->avg_vol > 20000)
			{
				$company = $this->BuildData($company_data);
				$company->macd = trader_macd($company->close,12,26,9);
				if($company->macd == false)
					continue;
			
				$company->rsi = trader_rsi($company->close,14);
				$company->ema10 = trader_ema($company->close,10);
				$company->ema25 = trader_ema($company->close,25);
				$company->bullish = new \StdClass();
				
				$this->Bullish_OpenPriceIndicator($company);
				
				$last_date = Carbon::createFromFormat('Y-m-d',$company->last_date);
				
				if($last_date < $last_week )
				{
					continue;
				}
				
				$options=['upsert'=>true];
				$query=['symbol'=>$company->symbol];
				$this->db->companies->updateOne($query,['$set'=>$company],$options);
			}
		}
	}
	public function Bullish()
	{
		$projection = ["symbol"=>1,"_id"=>0];
		$companies = $this->MongoRead('companies',[],[],$projection)->toArray();
		$selected = [];
		foreach($companies as $company)
		{
			$query = ['symbol'=> $company->symbol];
			$company = $this->db->companies->findOne($query);
			if($company != null)
			{
				//if(isset($company->bullish->open['last_index']))
				//{
				//	$indicator_index = $company->bullish->open['last_index'];
				//	$indicator = $company->bullish->open[$indicator_index];
				//	$date = $company->date[$indicator_index];
				//	$selected[$date."_".$company->symbol] =  $company;
				//}
				$selected[$company->symbol] =  $company;
			}
		}
		
		krsort ($selected );
		
		$output = [];
		foreach($selected as $key=>$company)
		{
			$index = $company->last_index;
			$bullish_on_index = $company->bullish->open['last_index'];
				
			$o =  new \StdClass();
			$o->bullish_on_date = $company->date[$bullish_on_index];
			$o->date = $company->date[$index];
			if($bullish_on_index == $company->last_index)
				$o->recent=1;
			else
				$o->recent=0;
			$o->symbol = $company->symbol;
			$o->avg_vol = $company->avg_vol;
			$o->ema10 = $company->ema10[$index];
			$o->ema25 = $company->ema25[$index];
			foreach($company->ema25 as $key=>$ema25)
			{
				$o->ema_diff[$key]=$company->ema10[$key]-$company->ema25[$key];
				
			}
			$o->rsi = $company->rsi[$index];
			$o->macd='';
			if(isset($company->macd[2][$index]))
				$o->macd = $company->macd[2][$index];
			
			$o->vol = $company->vol[$index];
			//$o->change = $company->change[$index];
			$o->open = $company->open[$index];
			$o->close = $company->close[$index];
			$o->prev_close = $company->close[$index-1];
			$o->high = $company->high[$index];
			$o->low = $company->low[$index];
			$o->prev_vol = $company->vol[$index-1];
			$o->vol = $company->vol[$index];
			
			$o->open_graph = array_slice((array)$company->open,-14,14);
			
			$o->high_graph = array_slice((array)$company->high,-14,14);
			$o->low_graph = array_slice((array)$company->low,-14,14);
			$o->price_graph = array_slice((array)$company->close,-14,14);
			$o->vol_graph = array_slice((array)$company->vol,-14,14);
			
			$o->bullish = new \StdClass();
			$o->bullish->open = $company->bullish->open[$index];
			//$o->candle = $company->candle[$index];
			$o->macd_graph = array_slice((array)$company->macd[2]->jsonSerialize(),-14,14);
			$o->ema_graph = array_slice((array)$o->ema_diff,-14,14);
			$o->change = round($company->change[$index],2);
		
			$candle = $company->candle[$index];
			//dump($candle);
			//O=179.61  L=177.39 C=177 H=181.70
			//O=129     L=127.68 C=126 H=129.89
			//if($company->symbol == 'TRG')
			//	dd($candle);
			
			$body = abs($candle->body);
			$o->box = array(0, $candle->ushadow, $candle->ushadow+$body, $candle->ushadow+$body,$candle->ushadow+$body+$candle->lshadow);
			
			$output[] = $o;
			
		}
		return $output;
	}
	public function Script2()
	{
		$green = "\e[0;31;32m";
		$red = "\e[0;31;31m";
		$yellow = "\e[0;31;93m";
		$orange = "\e[0;31;91m";
		$nocol = "\e[0;31;39m";
		$lightgreen = "\e[0;31;92m";
		
		//$query = ["data.date"=> "2021-02-01"];
		//$projection = ["symbol"=>1, "vol"=>1];
		//dd(count($this->MongoRead('companies',$query,[],$projection)->toArray()));
		
		//$data = $this->db->companies->find(["data.date"=> "2021-02-01"],[],[)->toArray();		
		//dd(count($data));
		$dailydata = $this->DailyDataObjects();
		$this->company_data = $this->Populate($dailydata);
		$this->db->companies->insertMany(array_values($this->company_data));
		
		/*foreach($this->company_data as $company)
		{
			if($company->vol != 0)
				dump($company->symbol."  ".$company->name."  ".$company->vol);
		}
		dd('ff');*/
		$today =  new Carbon();
		$today =  $today->format('Y-m-d');
		$yesteday = Carbon::now()->subDays(1);
		foreach($this->company_data as $symbol=>$data)
		{
			$result = $this->GetData($symbol);
			$macd = trader_macd($result->close,12,26,9);
			if($macd == false)
				continue;
			$rsi = trader_rsi($result->close,14);
			$ema10 = trader_ema($result->close,10);
			$ema25 = trader_ema($result->close,25);
			//mumtaz
			$this->UpdateCandles($result);
			$index = count($result->indicator_open)-1;
			
			$date = $result->date[$index];
			$rsi = round($rsi[$index]);
			$close = $result->close[$index];
			$ema10_ = round($ema10[$index],2);
			$ema25_= round($ema25[$index],2);
			
			if($date == $today)
				$symbol_col = $lightgreen;
			else if($date == $yesteday)
				$symbol_col = $green;
			else
				$symbol_col = $nocol;
			
			$date_col = $symbol_col;
			$close_col = $symbol_col;
			
			if($rsi > 70)
				$rsi_col = $orange;
			else if($rsi > 35)
				$rsi_col = $yellow;
			else if($rsi > 0)
				$rsi_col = $lightgreen;
			
			
			if($ema10_ > $ema25_)
			{
				$ema_color = $lightgreen;
				if($close < $ema10_ )
					$close_col = $orange;
				else if($close < $ema25_ )
					$close_col = $yellow;
				else 
					$close_col = $lightgreen;
			}
			else
			{
				$ema_color = $yellow;
				if($close < $ema25_ )
					$close_col = $orange;
				else if($close < $ema10_ )
					$close_col = $yellow;
				else 
					$close_col = $lightgreen;
			}
			
			
			//if($close <
			//$result->close[$index]."  ".$macd[2][$index]els
			
			$format = $symbol_col."%7s ".$date_col."%10s ".$close_col."%7.2f ".$rsi_col."%4s ".$ema_color."[%7.2f - %7.2f]"."\n".$nocol;
			
			
			if($result->indicator_open[$index] > 0)
			{
				//dump($symbol." ".$result->date[$index]."  ".$result->close[$index]."  ".$macd[2][$index]."  ".$rsi[$index]." ".$ema10[$index]." ".$ema25[$index]);
				echo sprintf($format,$symbol,$date,$close,$rsi,$ema10_,$ema25_);
			}
	//$format = "%7s %4s %10s ".$price_col." %6.2f ".$change_col." %6.2f ".$nocol."ema[".$ema_col."%6.2f ".$ema_col."%6.2f".$nocol."] macd=".$macd_col."%6.2f ".$reversal_col.$reversal." vol=".$volume_col." %d(%4.2f)".$rsi_col." rsi=%2.1f\n".$nocol;
			
			
	//echo sprintf($format,$symbol,$signal,$date,$closing,$change,$ema10_,$ema25_,$macd_,$volume,$percent_volume,$rsi_);
			
		
		}
		dd('ff');
		
		$obv = trader_obv ($result->close, $result->vol );
		
		$macd2 = trader_macdext ($result->close, 26, TRADER_MA_TYPE_EMA, 12 , TRADER_MA_TYPE_EMA , 9 , TRADER_MA_TYPE_EMA);
		//$trix = trader_trix ($res\nult->close, 15 );
		//$ppo = trader_ppo ( $result->close, 10, 25, TRADER_MA_TYPE_EMA );
		foreach($obv  as $key=>$value)
		{
			$date=$result->date[$key];
			$close=$result->close[$key];
			$vol=$result->vol[$key];
			echo $date.",".$close.",".$obv[$key]."\n";

		}
		dd('hh');
		//dd($values);
		//$values = $this->macd($result->close);
		dd(trader_trix ($result->close, 15 ));
		dd(trader_min ( $result->close, 15 ));
		$open_ema = trader_ema($result->open,10);
		$open_rsi = trader_rsi ($result->open, 14);
		$close_rsi = trader_rsi ($result->close, 14);
		//dump($open_ema);
		//dd($rsi);
		//dump($result->open);
		//dd($open_ema);
		foreach($open_rsi as $key=>$value)
		{
			$rsi_index = $key-13;
			
			echo $result->date[$key].",".$value.",".$result->open[$key].",".$result->close[$key].",".$open_rsi[$key].",".$close_rsi[$key]."\n";
		}
		dd(end($result->open));

		
		foreach($values[2] as $key=>$value)
		{
			$date=$result->date[$key];
			$close=$result->close[$key];
			$vol=$result->vol[$key];
			$open=$result->open[$key];
			$rsi_ = $rsi[$key];
			echo $date."  ".$rsi_." ".$open." ".$close." ".$values[2][$key]."   ".$values[7][$key]."  ".$vol."\n";
			
		}
		dd('eee');
		//dd(trader_rsi ($result->close, 14));
		//dd(trader_cdlbreakaway ( $result->open , $result->high , $result->low , $result->close ));
		//$values = trader_cdlengulfing ( $result->open , $result->high , $result->low , $result->close );
		//$values = trader_adx (  $result->high , $result->low , $result->close ,14);
		//$values = trader_cci ( $result->high , $result->low , $result->close ,14);
		$values =trader_mfi ( $result->high , $result->low , $result->close , $result->vol ,14 );

		//$values =  trader_apo ( $result->close , 10 , 25 , TRADER_MA_TYPE_EMA );
		foreach($values as $key=>$value)
		{
			$date=$result->date[$key];
			$close=$result->close[$key];	
			echo $date."  ".$close." ".$values[$key]."\n";
		}
		//trader_plus_dm ( array $high , array $low , int $timePeriod = ? ) : array
		//dump($this->company_data['ANL']);
		//$daydata = $this->GetDayData('ANL');
		//dd(trader_ema ( $result->close, 25));
		//dd(trader_ema ( $result->close, 10));
		//$values = trader_willr ( $result->high , $result->low , $result->close , 14 );
		
		//foreach($values[2] as $key=>$value)
		//{
		//	$date=$result->date[$key];
		//	$close=$result->close[$key];
		//	
		//	echo $date."  ".$close." ".$values[2][$key]."   ".$values[3][$key]."  ".$values[4][$key]."\n";
			
		//}
		
		dd('dd');
		dd(trader_willr ( $result->high , $result->low , $result->close , 14 ));
		dd(trader_plus_dm ($result->high , $result->low , 14 ));
		dd($daydata);
		dd('dd');
		//Carbon::now()->subDays(30)
		$period = CarbonPeriod::create(Carbon::now()->subDays(90),Carbon::now());
		foreach ($period as $date) 
		{
			//$dt =  $date->format('Y-m-d');
			if($date->isWeekend())
			{
				$obj =  new \StdClass();
				$obj->date = $date;
			}	
		}
		dd('ff');
		foreach($this->scripts as $title=>$list)
		{
			dump('*******************'.$title.'*********************');
			foreach($list as $symbol)
			{
				$today = $this->CurrentDateTimeObj()->format('Y-m-d');
				$pastdata = $this->GetPastData($symbol);
				if($pastdata->average_volume < 10000)
					continue;
				$daydata = $this->GetDayData($symbol);
				//dd($daydata->date);
				if($daydata != null)
				{
					if($pastdata->latest != $daydata->date)
					{
						$pastdata->average_volume = ($daydata->volume+$pastdata->average_volume)/2;
						$lastclosing = end($pastdata->closing);
						$pastdata->closing[]=$daydata->cur;
						$pastdata->volumes[]=$daydata->volume;
						$pastdata->change[]=$daydata->cur-$lastclosing;
						$pastdata->dates[]=$daydata->time[0];
					}
				}
				
				$this->EMAIndicator($symbol,$pastdata,$pastdata->average_volume);
			}
			break;
		}
		dd('Done');
		//dd($pastdata[0]);
		$daydata = $this->GetDayData('ANL');
		dump($daydata->min);
		dump($daydata->max);
		$daydata = $this->GetPastData('ANL');
		dump($daydata->average_volume);
		$ch = curl_init();
		foreach($this->scripts as $title=>$list)
		{
			dump('*******************'.$title.'*********************');
			foreach($list as $symbol)
			{
				dump($symbol);
				$url = $this->url_day.$symbol;
				curl_setopt($ch, CURLOPT_URL,$url);
				//curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
				//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/txt'));
				//curl_setopt($ch, CURLOPT_POSTFIELDS,'dtype=byday&sdate=2021-01-06&rfdate=2020-01-09&rtdate=2021-01-07&mansear');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);
				$data = json_decode($server_output);
				$volume = 0;
				if(count($data->data) > 0)
				{
					//dump($data->data);
					foreach($data->data as $d)
						$volume += $d[2];
				
					$today = $data->data[0];
					$today[2]=$volume;
					$dt_today = new Carbon();
					$dt_today->setTimeStamp($today[0]);
					//dump($dt_today->format('Y-m-d'));
				}
				$url = $this->url_history.$symbol;
				curl_setopt($ch, CURLOPT_URL,$url);
				//curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
				//curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/txt'));
				//curl_setopt($ch, CURLOPT_POSTFIELDS,'dtype=byday&sdate=2021-01-06&rfdate=2020-01-09&rtdate=2021-01-07&mansear');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$server_output = curl_exec ($ch);
				$data = json_decode($server_output);
				$dt = new Carbon();
				$dt->setTimeStamp($data->data[0][0]);
				
				if($volume > 0)
				{
					if($dt_today->format('Y-m-d') != $dt->format('Y-m-d'))
					{
						dump("Merging todays data");
						$data = array_merge([$aor],$data->data);
					}
					else
						$data = $data->data;
				}
				else
					$data = $data->data;
				
				//dd($data);
				$closing = [];
				$dates = [];
				$i=0;
				$average_volume = 0;
				foreach($data as &$d)
				{	
				    $dt = new Carbon();
					$dt->setTimeStamp($d[0]);
					//dump($dt->format('Y-m-d'));
					$d[0] = $dt->format('Y-m-d h:i');
					
					$closing [] = $d[1];
					$volumes [] = $d[2];
					$average_volume += $d[2];
					$dates[] = $dt->format('Y-m-d'); 
					$i++;
					if($i == 100)
						break;
					//if($i < 60)
					//	$dates[] = $dt->format('Y-m-d'); 
				}
				$dates = array_reverse ( $dates);
				$closing = array_reverse($closing);
				
				$average_volume = $average_volume/(count($closing));
				
				$ema25 = trader_ema ($closing ,25 );
				
				$ema10 = trader_ema ($closing ,10 );
				
				$bbands =trader_bbands ($closing , 25,TRADER_REAL_MIN,
								TRADER_REAL_MIN,
								TRADER_MA_TYPE_EMA);
				$macd = trader_macd ($closing, 12 , 26 , 9 );
				
				$startindex=0;
				foreach($macd[0]  as $startindex=>$m)
					break;
				$endindex = count($dates)-1;
				
				$prev_macd = null;
				$prev_closing = null;
				$signal_macd=-1;
				$pmomentum = 0;
				$nmomentum = 0;
				$state= '';
				$substate = '';
				$probability = '';
				
				for($i=$startindex; $i<=$endindex; $i++)
				{
					if($prev_macd != null)
					{
						if(($prev_macd <= 0)&&($macd[2][$i]>0))
						{
							$signal_macd=1;
							$state="Bullish on ".$dates[$i]." @".$closing[$i]." macd=".$macd[2][$i];
							if(($closing[$i] < $ema25[$i])&&($closing[$i] < $ema10[$i]))
								$probability = 'low '.($closing[$i]-$prev_closing);
							else
								$probability = 'high '.($closing[$i]-$prev_closing);
							$substate = '';
						}
						if(($prev_macd >= 0)&&($macd[2][$i]<0))
						{
							$signal_macd=0;
							$state="Bearish on ".$dates[$i]." @".$closing[$i]." macd=".$macd[2][$i];
							$substate = '';
							if(($closing[$i] > $ema25[$i])||($closing[$i] > $ema10[$i]))
								$probability = 'low '.($closing[$i]-$prev_closing);
							else
								$probability = 'high '.($closing[$i]-$prev_closing);
						}
						if($prev_macd < $macd[2][$i])
						{
							//dump("+ive momentum");
							$nmomentum=0;
							$pmomentum++;
						}
						else
						{
							//dump("-ive momentum");
							$pmomentum=0;
							$nmomentum++;
						}
						if($pmomentum >= 4)
						{
							if($signal_macd==0)
							{
								//dump("+ive reversal @".$closing[$i]." ".$dates[$i]);
								$substate =" +ive reversal @".$closing[$i]." ".$dates[$i]." macd=".$macd[2][$i];
							}
							$pmomentum=0;
						}
						if($nmomentum >= 4)
						{
							if($signal_macd==1)
							{
								//dump("-ive reversal @".$closing[$i]." ".$dates[$i]);
								$substate = "-ive reversal @".$closing[$i]." ".$dates[$i]." macd=".$macd[2][$i];
							}
							$nmomentum=0;
						}
					}
					$prev_macd = $macd[2][$i];
					$prev_closing = $closing[$i];
				}
				dump($state."  ".$probability);
				dump($substate);
				dump("Todays rate = ".$closing[$endindex]." macd=".$macd[2][$endindex]." ema25=".$ema25[$endindex]." ema10=".$ema10[$endindex]);
				dump("**********************");
			}
		}
	}
}