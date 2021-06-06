<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('/sample','App\Http\Controllers\Sample\SampleController@sync')->name('sample.sync');
/////////////////////////////////////////////
Route::get('/support','App\Http\Controllers\Support\SupportController@active')->name('support.active');
Route::get('/support/sync','App\Http\Controllers\Support\SupportController@sync')->name('support.sync');
Route::get('/support/closed','App\Http\Controllers\Support\SupportController@closed')->name('support.closed');
Route::get('/support/updated','App\Http\Controllers\Support\SupportController@updated')->name('support.updated');

//////////////////////////////////////////
Route::get('/ishipment','App\Http\Controllers\Ishipment\IshipmentController@active')->name('ishipment.active');
Route::get('/ishipment/sync','App\Http\Controllers\Ishipment\IshipmentController@sync')->name('ishipment.sync');
Route::get('/ishipment/issynced','App\Http\Controllers\Ishipment\IshipmentController@issynced')->name('ishipment.issynced');

//////////////////////////////////////////
Route::get('/lshipment/sync','App\Http\Controllers\Lshipment\LshipmentController@sync')->name('lshipment.sync');
Route::get('/lshipment/issynced','App\Http\Controllers\Lshipment\LshipmentController@issynced')->name('lshipment.issynced');
Route::get('/lshipment/{team?}/{code?}','App\Http\Controllers\Lshipment\LshipmentController@active')->name('lshipment.active');

//////////////////////////////////////////
Route::get('/epicupdate/sync','App\Http\Controllers\Epicupdate\EpicupdateController@sync')->name('epicupdate.sync');

//////////////////////////////////////////
Route::get('/sprintcalendar','App\Http\Controllers\Sprintcalendar\SprintcalendarController@show')->name('sprintcalendar.show');

/////////////////////////////////////////
Route::get('/milestone','App\Http\Controllers\Milestone\MilestoneController@show')->name('milestone.show');

/////////////////////////////////////////
Route::get('/bspestimate','App\Http\Controllers\Bspestimate\BspestimateController@show')->name('bspestimate.show');
Route::get('/bspestimate/driver/search/{identification}','App\Http\Controllers\Bspestimate\BspestimateController@searchdriver')->name('bspestimate.searchdriver');
Route::get('/bspestimate/driver/estimate/{target}/{identification}','App\Http\Controllers\Bspestimate\BspestimateController@estimate')->name('bspestimate.estimate');
Route::get('/bspestimate/plan','App\Http\Controllers\Bspestimate\BspestimateController@plan')->name('bspestimate.plan');
Route::get('/bspestimate/commontasks','App\Http\Controllers\Bspestimate\BspestimateController@CommonTasks')->name('bspestimate.commontasks');
Route::get('/bspestimate/search','App\Http\Controllers\Bspestimate\BspestimateController@Search')->name('bspestimate.search');
Route::get('/bspestimate/sync','App\Http\Controllers\Bspestimate\BspestimateController@Sync')->name('bspestimate.sync');

/////////////////////////////////////////

Route::get('/cveportal','App\Http\Controllers\Cveportal\CveportalController@index')->name('cveportal.index');
Route::get('/cveportal/cve/{group}/{product}/{version}/{admin?}/{organization?}','App\Http\Controllers\Cveportal\CveportalController@getcves')->name('cveportal.getcves');
Route::get('/cveportal/login','App\Http\Controllers\Cveportal\CveportalController@login')->name('cveportal.login');
Route::get('/cveportal/logout', 'App\Http\Controllers\Cveportal\CveportalController@logout')->name('cveportal.logout'); 
Route::post('/cveportal/authenticate','App\Http\Controllers\Cveportal\CveportalController@authenticate')->name('cveportal.authenticate');
Route::get('/cveportal/authenticate','App\Http\Controllers\Cveportal\CveportalController@authenticate')->name('cveportal.authenticate');

Route::get('/cveportal/triage','App\Http\Controllers\Cveportal\CveportalController@triage')->name('cveportal.triage');
Route::put('/cveportal/status/update','App\Http\Controllers\Cveportal\CveportalController@statusupdate')->name('cveportal.status.update');
Route::get('/cveportal/jira/sync/request','App\Http\Controllers\Cveportal\CveportalController@jirasyncrequest')->name('cveportal.jirasyncrequest');
Route::get('/cveportal/rest/product/{id}','App\Http\Controllers\Cveportal\CveportalController@getproductdata')->name('cveportal.getproductdata');
Route::get('/cveportal/rest/cve/{productid}','App\Http\Controllers\Cveportal\CveportalController@getcvedata')->name('cveportal.getcvedata');
Route::get('/cveportal/rss/cve/{productid}','App\Http\Controllers\Cveportal\CveportalController@rssfeed')->name('cveportal.rssfeed');
Route::get('/cveportal/sync','App\Http\Controllers\Cveportal\CveportalController@SyncRequest')->name('cveportal.sync');
Route::get('/cveportal/issyncrequested','App\Http\Controllers\Cveportal\CveportalController@IsSyncRequested')->name('cveportal.issyncrequested');



/////////////////////////////////////////

Route::get('/psx/sync','App\Http\Controllers\Psx\PsxController@sync')->name('psx.sync');
Route::get('/psx/bullish','App\Http\Controllers\Psx\PsxController@bullish')->name('psx.bullish');
Route::get('/psx/graph/{symbol}','App\Http\Controllers\Psx\PsxController@graph')->name('psx.graph');


/////////////////////////////////////////
Route::get('/oss/hits/{project}/{id?}','App\Http\Controllers\Cryptography\CryptController@showhits')->name('oss.showhits');
Route::get('/oss/file/{project}/{id?}','App\Http\Controllers\Cryptography\CryptController@showfile')->name('oss.showfile');
Route::get('/oss/{product}/{package?}','App\Http\Controllers\Cryptography\CryptController@showproduct')->name('oss.showproduct');
Route::put('/oss/triage','App\Http\Controllers\Cryptography\CryptController@triage')->name('oss.triage');
Route::get('/oss/search/{project}/{package?}/{hitid}','App\Http\Controllers\Cryptography\CryptController@search')->name('oss.search');


/////////////////////////////////////////
Route::get('/{param1?}/{param2?}/{param3?}', function (Request $request,$param1=null,$param2=null,$param3=null) 
{
	$url = Request::root();
	$parts = explode('shipments.pkl.mentorg.com',$url);
	if(count($parts)>1&&('http://' == strtolower($parts[0])))
	{
		if(($param1 == 'international')||($param1 == null))
		{
			return \Redirect::route('ishipment.active',[]);
		}
		else if($param1 == 'local')
			return \Redirect::route('lshipment.active', ['team'=>$param2,'code'=>$param3]);
	}
	$parts = explode('localshipments.pkl.mentorg.com',$url);
	if(count($parts)>1&&('http://' == strtolower($parts[0])))
	{
		if($param1==null)
			return view('default');
		return \Redirect::route('lshipment.active', ['team'=>$param1,'code'=>$param2]);
	}
	$parts = explode('rmo.pkl.mentorg.com',$url);
	if(count($parts)>1&&('http://' == strtolower($parts[0])))
	{
		return \Redirect::route('rmo.'.$param1, []);
	}
	$parts = explode('support.pkl.mentorg.com',$url);
	if(count($parts)>1&&('http://' == strtolower($parts[0])))
	{
		return \Redirect::route('support.active', []);
	}
	
	
	
    return view('default');
});
Route::get('/', function () {
    return view('default');
});
