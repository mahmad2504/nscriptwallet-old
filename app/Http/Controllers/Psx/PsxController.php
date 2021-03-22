<?php

namespace App\Http\Controllers\Psx;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Psx\Indicators;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class PsxController extends Controller
{
	public function Sync(Request $request)
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
}
