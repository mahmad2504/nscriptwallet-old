<?php

namespace App\Http\Controllers\Sample;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Sample\Sample;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class SampleController extends Controller
{
	public function Sync(Request $request)
	{
		$app = new Sample();
		$app->Save(['sync_requested'=>1]);
		return ['status'=>'Sync Requested'];
	}
 	public function Sync(Request $request)
    {
		$app = new Sample();
		dump(Artisan::queue('sample:sync', []));
		dump("last updated on ".$app->ReadUpdateTime());
	}
}
