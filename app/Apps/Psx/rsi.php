<?php
namespace App\Apps\Psx;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;

use Illuminate\Notifications\Notifiable;
use App\Notifications\Telegram;
use NotificationChannels\Telegram\TelegramMessage;

class Rsi extends Psx{
	use Notifiable;
	public $scriptname = 'psx:rsi';
	public $options = 0;
	public $url_day = "https://dps.psx.com.pk/timeseries/int/";
	public $url_history = "https://dps.psx.com.pk/timeseries/eod/";
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
	public function Script()
	{
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