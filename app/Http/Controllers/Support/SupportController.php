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
			$ticket->updated = $dt->format('Y-m-d');
			if($ticket->resolutiondate != '')
			{
				$dt = CDateTime($ticket->resolutiondate,$app->timezone);
				$ticket->resolutiondate = $dt->format('Y-m-d');
			}
			else
				$ticket->resolutiondate = '';
			
			if($ticket->solution_provided_date != '')
			{
				$dt = CDateTime($ticket->solution_provided_date,$app->timezone);
				$ticket->solution_provided_date = $dt->format('Y-m-d');
			}
			else
				$ticket->solution_provided_date = '';
			
			
			
			$ticket->product_name=$this->ParseProductName($ticket->product_name);
		}
		$last_updated = $app->ReadUpdateTime('lastupdate');
		$jira_url = env('JIRA_EPS_URL','')."/browse/";
		$page='active';
		return view('support.home',compact('tickets','last_updated','jira_url','page'));
	}
	public function Closed(Request $request)
	{
		$app = new Support();
		$tickets = $app->ClosedTickets();
		foreach($tickets as $ticket)
		{
			$dt = CDateTime($ticket->created,$app->timezone);
			$ticket->created = $dt->format('Y-m-d');
			$ticket->updated = $dt->format('Y-m-d');
			$ticket->product_name=$this->ParseProductName($ticket->product_name);
			if($ticket->resolutiondate != '')
			{
				$dt = CDateTime($ticket->resolutiondate,$app->timezone);
				$ticket->resolutiondate = $dt->format('Y-m-d');
			}
			else
				$ticket->resolutiondate = '';
			
			if($ticket->solution_provided_date != '')
			{
				$dt = CDateTime($ticket->solution_provided_date,$app->timezone);
				$ticket->solution_provided_date = $dt->format('Y-m-d');
			}
			else
				$ticket->solution_provided_date = '';
		}
		$last_updated = $app->ReadUpdateTime('lastupdate');
		$jira_url = env('JIRA_EPS_URL','')."/browse/";
		$page='closed';
		return view('support.home',compact('tickets','last_updated','jira_url','page'));
	}
	public function Updated(Request $request)
	{
		$app = new Support();
		$tickets = $app->UpdatedTickets();
		foreach($tickets as $ticket)
		{
			$dt = CDateTime($ticket->created,$app->timezone);
			$ticket->created = $dt->format('Y-m-d');
			$ticket->updated = $dt->format('Y-m-d');
			$ticket->product_name=$this->ParseProductName($ticket->product_name);
			if($ticket->resolutiondate != '')
			{
			$dt = CDateTime($ticket->resolutiondate,$app->timezone);
			$ticket->resolutiondate = $dt->format('Y-m-d');
			}
			else
				$ticket->resolutiondate = '';
			
			if($ticket->solution_provided_date != '')
			{
				$dt = CDateTime($ticket->solution_provided_date,$app->timezone);
				$ticket->solution_provided_date = $dt->format('Y-m-d');
			}
			else
				$ticket->solution_provided_date = '';
		}
		$last_updated = $app->ReadUpdateTime('lastupdate');
		$jira_url = env('JIRA_EPS_URL','')."/browse/";
		$page='updated';
		return view('support.home',compact('tickets','last_updated','jira_url','page'));
	}
}
