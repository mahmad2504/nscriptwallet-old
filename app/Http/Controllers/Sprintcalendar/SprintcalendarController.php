<?php

namespace App\Http\Controllers\Sprintcalendar;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Sprintcalendar\Sprintcalendar;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class SprintcalendarController extends Controller
{
	public function Show(Request $request)
	{
		$start = Carbon::now();
		$start->subDays(63);
		$end = Carbon::now();
		$end=  $end->addDays(300);
		
		//ob_start('ob_gzhandler');

		$calendar =  new SprintCalendar($start,$end);
		$tabledata = $calendar->GetGridData();
		return View('sprintcalendar.index',compact('tabledata'));
	}
}
