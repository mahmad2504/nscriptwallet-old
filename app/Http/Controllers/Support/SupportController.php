<?php

namespace App\Http\Controllers\Support;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Support\Support;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class SupportController extends Controller
{
 	public function Sync(Request $request)
    {
		dump(Artisan::queue('support:sync', []));
	}
	
	private function ParseProductName($name)
	{
		$p = explode(">",$name);
		if(count($p)==3)
			$product_name = explode("<",$p[1])[0];
		else
			$product_name = $name;
		return $product_name;
	}
	public function Active(Request $request)
	{
		$app = new Support();
		$tickets = $app->ActiveTickets();
		foreach($tickets as $ticket)
		{
			$dt = CDateTime($ticket->created,$app->timezone);
			$ticket->created = $dt->format('Y-m-d');
			$dt = CDateTime($ticket->resolutiondate,$app->timezone);
			$ticket->resolutiondate = $dt->format('Y-m-d');
			$ticket->product_name=$this->ParseProductName($ticket->product_name);
		}
		$last_updated = $app->ReadUpdateTime('lastupdate');
		$jira_url = env('JIRA_EPS_URL','')."/browse/";
		return view('support.home',compact('tickets','last_updated','jira_url'));
	}
	public function Closed(Request $request)
	{
		$app = new Support();
		$tickets = $app->ClosedTickets();
		foreach($tickets as $ticket)
		{
			$dt = CDateTime($ticket->created,$app->timezone);
			$ticket->created = $dt->format('Y-m-d');
			$dt = CDateTime($ticket->resolutiondate,$app->timezone);
			$ticket->resolutiondate = $dt->format('Y-m-d');
			$ticket->product_name=$this->ParseProductName($ticket->product_name);
		}
		$last_updated = $app->ReadUpdateTime('lastupdate');
		$jira_url = env('JIRA_EPS_URL','')."/browse/";
		return view('support.home',compact('tickets','last_updated','jira_url'));
	}
}
