<?php

namespace App\Http\Controllers\Ishipment;
use App\Apps\Ishipment\Ishipment;
use App\Http\Controllers\Controller;

use Auth;
use Illuminate\Http\Request;
use Response;

class IshipmentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
	
    }
	public function Active(Request $request)
	{
		$app = new Ishipment();
		$tickets = $app->ReadActive();
		$filtered = [];
		for($i=0;$i<count($tickets);$i++)
		{
			$ticket = $tickets[$i];
			$obj =  new \StdClass();
			$ticket = $ticket->jsonSerialize();
			unset($ticket->_id);
			/// Hardware Details //////
			$parts = explode("Qty:",$ticket->desc);
			$parts = explode("\n",$parts[0]);	
			$del = '';
			$hardware = '';
			for($j=1;$j<count($parts);$j++)
			{
				if(strlen(trim($parts[$j]))>0)
				{
					$parts[$j] = str_replace("-",'',$parts[$j]);
					$hardware .= $del.trim($parts[$j]);
					$del=',';
				}
			}
			$obj->hardware = $hardware;
			dump($obj->hardware);
			dump($ticket->url);
			// Owener ////
			$parts = explode("-",$ticket->name);
			$obj->owner = $parts[2];
			$obj->source = $parts[1];
			$obj->team = '';
			foreach($ticket->labels as $label)
			{
				$obj->team = trim($label->name);
				break;
			}
			if(isset($ticket->checkitems['Shipment Dispatched']->state))
			{
				if($ticket->checkitems['Shipment Dispatched']->state == 'complete')
				{
					$obj->shipment_date = $ticket->checkitems['Shipment Dispatched']->date;
				}
			}
			$obj->trackingno = $ticket->trackingno;
			if(($ticket->list == 'Upcoming')||($ticket->list == 'Shipment')) 
			    $obj->status = 'Ready';
			if($ticket->checkitems['Shipment Dispatched'] == 'complete')
				$obj->status = 'Dispatched';
			if($ticket->list == 'Custom')
				$obj->status = 'Customs';
			if($ticket->list == 'Expense')
				$obj->status = 'Received';
			$obj->url = $ticket->url;
			$filtered[]=$obj;
		}
		$tickets = $filtered;
		$lastupdated = $app->ReadUpdateTime('lastupdate');
		return view('ishipment.index',compact('tickets','lastupdated'));
	}
}