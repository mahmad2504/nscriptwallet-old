<?php

namespace App\Http\Controllers\Psx;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Psx\Indicators;
use Redirect,Response, Artisan;
use Carbon\Carbon;


class PsxController extends Controller
{
	public function Sync(
	Request $request)
	{
		$app = new Indicators(['force'=>'1']);
		$app->run();
	}
	public function Bullish(Request $request)
	{
		$app = new Indicators();
		$data = $app->Bullish();
		return view('psx.bullish',compact('data'));
	}
	public function Graph(Request $request,$symbol)
	{
		//$url='http://www.scstrade.com/stockscreening/SS_BasicChart.aspx?symbol=UBL&ChartDirectorChartImage=chart_ContentPlaceHolder1_WebChartViewer1&cacheId=a0b54711daa349a991c865c54385030a&cacheDefeat=637542779406351567';
		//return Redirect::to($url);
		//return view('psx.graph',compact('url'));
		
		$data = file_get_contents('http://www.scstrade.com/stockscreening/SS_BasicChart.aspx?symbol=UBL');
		$cacheDefeat = explode('cacheDefeat=',$data)[1];
		$cacheDefeat = explode('"',$cacheDefeat)[0];
		
		$cacheId = explode('cacheId=',$data)[1];
		$cacheId = explode('&',$cacheId)[0];
		$url = 'http://www.scstrade.com/stockscreening/SS_BasicChart.aspx?symbol=UBL&ChartDirectorChartImage=chart_ContentPlaceHolder1_WebChartViewer1&cacheId='.$cacheId.'&cacheDefeat='.$cacheDefeat;
		dump($url);
		sleep(5);
		return view('psx.graph',compact('url'));
	}
}
