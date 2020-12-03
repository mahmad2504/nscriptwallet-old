<?php

namespace App\Http\Controllers\lshipment;
use App\Apps\lshipment\Lshipment;
use App\Http\Controllers\Controller;

use Auth;
use Illuminate\Http\Request;
use Response;

class LshipmentController extends Controller
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
		$app = new Lshipment();
		$app->Save(['sync_requested'=>1]);
		return ['status'=>'Sync Requested'];
	}
	public function Active(Request $request,$team,$code)
	{
		if(strlen($code) < 5)
		{
			return ['result'=>'Unautorized Aceess'];
		}
		
		if( count(explode($code,md5(strtolower($team))))==2)
		{}
		else
			return ['result'=>'Unautorized Aceess'];
		
		$app = new Lshipment();
		$tickets = $app->ReadActive();
		
		$filtered = [];
		for($i=0;$i<count($tickets);$i++)
		{
			//dump($tickets[$i]->closed);
			$tickets[$i] = $tickets[$i]->jsonSerialize();
			if(isset($tickets[$i]->archived))
				if($tickets[$i]->archived==1)
					dump($tickets[$i]);
			unset($tickets[$i]->_id);
			$tickets[$i]->index= $i;
			//unset($tickets[$i]->id);
			$desc = str_replace("\n"," ",$tickets[$i]->desc);
			$parts = explode('**',$desc);
			$debug=0;
			if($tickets[$i]->url == 'https://trello.com/c/qPiSIkZ4/735-multiple-hardware-office-office')
				$debug=1;
			
			if(count($parts)>2)
			{
				$tickets[$i]->details = trim($parts[2]);
				if(strlen($tickets[$i]->details)>0)
				{
					if($tickets[$i]->details[0] == '-')
						$tickets[$i]->details[0]=" ";
				}
			}
			else
				$tickets[$i]->details = "Not Found";
			
			$tickets[$i]->details=trim($tickets[$i]->details);
			
			
			if(count($parts)>4)
				$tickets[$i]->source = $parts[4];
			else
				$tickets[$i]->source = "Not Found";
			
			if(count($parts)>6)
				$tickets[$i]->dest = $parts[6];
			else
				$tickets[$i]->dest = "Not Found";
			
			if(count($parts)>8)
				$tickets[$i]->priority = $parts[8];
			else
				$tickets[$i]->priority = "Low";
			
			if(count($parts)>10)
				$tickets[$i]->requestor = trim($parts[10]);
			else
				$tickets[$i]->requestor = "";
			
			if(strlen($tickets[$i]->requestor)>0)
			{
				if(is_numeric(trim($tickets[$i]->requestor[0])))
					$tickets[$i]->requestor = "";
	
			}
			$parts = explode("-",$tickets[$i]->priority);

			$tickets[$i]->priority = trim($parts[0]);
			$tickets[$i]->priority_tip = '';
			if(isset($parts[1]))
				$tickets[$i]->priority_tip = $parts[1];
			
			if( (strtolower($tickets[$i]->priority)=='high')||(strtolower($tickets[$i]->priority)=='urgent')||(strtolower($tickets[$i]->priority)=='low'))
			{
			}
			else
				$tickets[$i]->priority = 'Low';
			
			$tickets[$i]->label = 'Others';
			foreach($tickets[$i]->labels as $label)
			{
				$tickets[$i]->label = trim($label->name);
				break;
			}
			
			
			if(strtolower($team)=='admin')
				$filtered[] = $tickets[$i];

			else 
			{
				if(strtolower($tickets[$i]->label) == strtolower($team))
					$filtered[] = $tickets[$i];
			}
			
		}
		$tickets = $filtered;
	
		$lastupdated = $app->ReadUpdateTime('lastupdate');
		
		$admin=0;
		if(strtolower($team)=='admin')
			$admin=1;
		
		$team = ucfirst($team);
		return view('Lshipment.index',compact('admin','tickets','lastupdated','team'));
	}
	public function AdminView(Request $request)
	{
		$db = new Database();
		$tickets = $db->ReadActive()->toArray();
		for($i=0;$i<count($tickets);$i++)
		{
			$tickets[$i] = $tickets[$i]->jsonSerialize();
			unset($tickets[$i]->_id);
			unset($tickets[$i]->id);
			$desc = str_replace("\n"," ",$tickets[$i]->desc);
			$parts = explode('**',$desc);
			if(count($parts)>2)
				$tickets[$i]->details = $parts[2];
			else
				$tickets[$i]->details = "Not Found";
			
			if(count($parts)>4)
				$tickets[$i]->source = $parts[4];
			else
				$tickets[$i]->source = "Not Found";
			
			if(count($parts)>6)
				$tickets[$i]->dest = $parts[6];
			else
				$tickets[$i]->dest = "Not Found";
		}
		$lastupdated = $app->ReadUpdateTime('lastupdate');

		$admin=1;
		return view('home',compact('admin','tickets','lastupdated'));
	}
	
}