<?php

namespace App\Http\Controllers\Epicupdate;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Epicupdate\Epicupdate;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class EpicupdateController extends Controller
{
	public function Sync(Request $request)
	{
		$app = new Epicupdate();
		$app->Save(['sync_requested'=>1]);
		return ['status'=>'Sync Requested'];
	}
}
