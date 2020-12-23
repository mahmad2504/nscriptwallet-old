<?php

namespace App\Http\Controllers\Bspestimate;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Bspestimate\Bspestimate;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class BspestimateController extends Controller
{
	public function Show(Request $request)
	{
		return view('bspestimate.index');
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
			$driver->estimates = $driver->estimates->jsonSerialize();
			unset($driver->_id);
		}
		return view('bspestimate.planner',compact('drivers'));
	}
}
