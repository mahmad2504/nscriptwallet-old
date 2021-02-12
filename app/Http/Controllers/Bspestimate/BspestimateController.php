<?php

namespace App\Http\Controllers\Bspestimate;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Bspestimate\Bspestimate;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class BspestimateController extends Controller
{
	public function TreeObject($title)
	{
		$obj = new \StdClass();
		$obj->title=$title;
		$obj->type='parent';
		$obj->readonly = 1;
		$obj->qa_estimate = '';
		$obj->dev_estimate = '';
		$obj->_children = [];
		return $obj;
	}
	public function Sync(Request $request)
	{
		$app = new Bspestimate();
		$app->Script();
		return ['status'=>'Done'];
	}
	public function Show(Request $request)
	{
		$tasks = $this->TreeObject("Standard Tasks");
		$dev = $this->TreeObject("Dev");
		$qa = $this->TreeObject("QA");
		
		/////////////////////////////////////////////////
		$app =  new Bspestimate();
		$dev->_children = $app->GetTeamTasks('dev');
		$total=0;
		foreach($dev->_children as $c)
		{
			$c->readonly=$c->immuteable;
			$c->dev_estimate = $c->estimate;
			$c->class = 'task';
			$total += $c->estimate;
		}
		$dev->dev_estimate = $total ;
		//////////////////////////////////////////////////
		$qa->_children = $app->GetTeamTasks('qa');
		$total=0;
		foreach($qa->_children as $c)
		{
			$c->readonly=$c->immuteable;
			$c->class = 'task';
			$c->qa_estimate = $c->estimate;
			$total += $c->estimate;
		}
		$qa->qa_estimate = $total ;
		///////////////////////////////////////////////////
		
		$tasks->_children[]=$dev;
		$tasks->_children[]=$qa;
		
		$tasks->qa_estimate = $qa->qa_estimate ;
		$tasks->dev_estimate = $dev->dev_estimate ;
		
		//////////////////////////////////////////////////
		$drivers = $this->TreeObject("Drivers");
		$drivers->_children = $app->GetDriverEstimates();
		$classes = [];
		foreach($drivers->_children as $c)
		{
			$c->title = $c->name;
			$c->qa_estimate = '';
			$c->dev_estimate = '';
			$c->identification = 'none';
			$classes[$c->class] = $c->class;
			foreach($c->children as $cc)
			{
				$cc->title = $cc->name;
				$cc->qa_estimate = '';
				$cc->dev_estimate = '';
				$cc->identification = 'none';
				if('standard feature' == $cc->type)
				{
					$cc->enabled = 1;
					$cc->readonly = 1;
				}
				else
				{
					$cc->enabled = 0;
					$cc->readonly = 0;
				}
				
				//data.enabled
				
			}
			$dvrs = $app->GetDrivers(['class'=>$c->class]);
			$options=[];
			$options['None']='None';
			$options['new driver'] = ['new driver'];
			
			foreach($dvrs as $driver)
			{
				if(count($driver->identifiers) ==0)
				{
					$options[$driver->name.":".$driver->product] = $driver->name.":nucleus ".$driver->product;
	
				}
				else
				{
					foreach($driver->identifiers as $identifier)
					{
						$options[$driver->name.":".$identifier.":".$driver->product] = $driver->name.":".$identifier.":nucleus ".$driver->product;
					}
				}
			}
			$c->selected_option = '';
			$c->options = $options;
			$c->selecedoption = '';
		}
		$drivers->dev_estimate = '';
		$drivers->qa_estimate= '';
		/*$driver_options=[];
		foreach($classes as $key=>$class)
		{
			$dvrs = $app->GetDrivers(['class'=>$class]);
			$options = [];
			foreach($dvrs as $driver)
			{
				foreach($driver->identifiers as $identifier)
				{
					$options[$driver->name.":".$identifier.":".$driver->product] = $driver->name.":".$identifier.":nucleus ".$driver->product;
				}
			}
			$driver_options[$class] = array_values($options);
		}*/
		return view('bspestimate.tabulator',compact('tasks','drivers'));
		//return view('bspestimate.index',compact('devtasks','qatasks'));
	}
	public function Search(Request $request)
	{
		$params = $request->all();
		$start = $params['start'];
		$search = $params['search'];
		$class = $params['class'];
		$app = new Bspestimate();
		
		$drivers = $app->GetDrivers(['class'=>$class]);
		
		//$obj = json_decode(file_get_contents('names.json'), true);
		$ret = array();
		foreach($drivers as $driver)
		{
			$driver->options = [];
			foreach($driver->identifiers as $identifier)
			{
				
				$driver->options[$driver->name.":".$identifier.":".$driver->product] = $driver->name.":".$identifier." in ".$driver->product;
			}
			$driver->options = array_values($driver->options);
			
		}
		dd($drivers);
		foreach($drivers as $driver)
		{
			if(preg_match('/' . ($start ? '^' : '') . $search . '/i', $driver->name))
			{
				//$ret[] = array('value' => $item['text'], 'text' => $item['text']);
				$ret[$driver->id] = $driver; 
			}
			foreach($driver->identifiers as $identifier)
			{
				$idfrs = explode(",",$identifier);
				foreach($idfrs as $idfr)
				{
					if(preg_match('/' . ($start ? '^' : '') . $search . '/i', $idfr))
					{
						unset($driver->_id);
						$ret[$driver->id] = $driver; 
					}
					
				}
			}
		}
		return array_values($ret);
	}
	public function CommonTasks(Request $request)
	{
		$bspestimate =  new Bspestimate();
		return $bspestimate->GetCommonTasks();
	}
	public function SearchDriver(Request $request,$identifier)
	{
		$app = new Bspestimate();
		$drivers = $app->SearchDrivers($identifier);
		foreach($drivers as &$driver)
		{
			$driver = $driver->jsonSerialize();
		}
		return $drivers;
	}
	public function Estimate($target,$identification)
	{
		
	}
	public function Plan(Request $request)
	{
		$app = new Bspestimate();
		$products = $app->GetProducts();
		$drivers = $app->GetDrivers();
		
		foreach($drivers as &$driver)
		{
			$driver = $driver->jsonSerialize();
			$driver->identifiers = $driver->identifiers->jsonSerialize();
			//$driver->estimate = $driver->estimate->jsonSerialize();
			unset($driver->_id);
		}
		return view('bspestimate.planner',compact('drivers'));
	}
}
