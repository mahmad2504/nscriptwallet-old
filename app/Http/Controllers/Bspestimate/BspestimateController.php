<?php

namespace App\Http\Controllers\Bspestimate;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class BspestimateController extends Controller
{
	public function Show(Request $request)
	{
		return view('bspestimate.index');
	}
}
