<?php

namespace App\Http\Controllers\Milestone;
use App\Apps\Milestone\Milestone;
use App\Http\Controllers\Controller;
use App\Apps\Sprintcalendar\Sprintcalendar;
use Carbon\Carbon;
use Auth;
use Illuminate\Http\Request;
use Response;

class MilestoneController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
	
    }
	public function Sync(Request $request)
	{
		$app = new Milestone();
		$app->Save(['sync_requested'=>1]);
		return ['status'=>'Sync Requested'];
	}
	public function Show(Request $request)
	{
		$now = Carbon::now();
		$start = Carbon::now();
		$start->subDays(90);
		$end = Carbon::now();
		$end=  $end->addDays(365);
		$calendar =  new Sprintcalendar($start,$end);
		$tabledata = $calendar->GetGridData();
		
		$milestone =  new Milestone();
		$tickets = $milestone->FetchTickets();
		$tickets = array_values($tickets);
		$now = $milestone->CurrentDateTimeObj();
		
		$versions = [];
		$versions[] = 'all';
		foreach($tickets as $ticket)
		{
			$duedate = new Carbon();
			$duedate->setTimeStamp($ticket->duedate);
			$ticket->delayed = 0;
			if($ticket->statuscategory != 'resolved')
			{
				if($duedate->getTimeStamp() < $now->getTimeStamp())
				{
					$ticket->delayed = $duedate->diffInDays($now);
				}
			}
			else //If resolved then find how much delayed
			{	
				$resolutiondate = new Carbon();
				$resolutiondate->setTimeStamp($ticket->resolutiondate);
				
				if($duedate->getTimeStamp() < $resolutiondate->getTimeStamp())
				{
					$ticket->delayed = $duedate->diffInDays($resolutiondate);
					//echo $ticket->key." ".$ticket->delayed."  ".$duedate->getTimeStamp()."  ".$resolutiondate->getTimeStamp()." ".$ticket->resolutiondate."<br>";
				}
			}
			foreach($ticket->fixVersions as &$fixVersion)
			{
				$fixVersion = strtolower($fixVersion);
				$versions[$fixVersion] = $fixVersion;
				break;
			}
							
			$ticket->duedate = $duedate->format('Y-m-d');
			$ticket->dueday = $duedate->format('d');
			$ticket->dueweek=$duedate->isoWeekYear()."_".$duedate->isoWeek();
		}
		$url = env('JIRA_EPS_URL');
		$versions = array_values($versions);
		return View('milestone.index',compact('tabledata','tickets','url','versions'));
	}
}