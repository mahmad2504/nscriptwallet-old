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

//////////////////////////////////////////
Route::get('/lshipment/sync','App\Http\Controllers\Lshipment\LshipmentController@sync')->name('lshipment.sync');
Route::get('/lshipment/{team}/{code}','App\Http\Controllers\Lshipment\LshipmentController@active')->name('lshipment.active');

//////////////////////////////////////////
Route::get('/epicupdate/sync','App\Http\Controllers\Epicupdate\EpicupdateController@sync')->name('epicupdate.sync');

//////////////////////////////////////////
Route::get('/sprintcalendar','App\Http\Controllers\Sprintcalendar\SprintcalendarController@show')->name('sprintcalendar.show');

Route::get('/{param1}/{params2?}', function (Request $request,$param1,$param2=null) 
{
	$url = Request::root();
	$parts = explode('shipments.pkl.mentorg.com',$url);
	if(count($parts)>1)
	{
		if($param1 == 'international')
		{
			return \Redirect::route('ishipment.active',[]);
		}
		else
			return \Redirect::route('lshipment.active', ['team'=>$param1,'code'=>$param2]);
	
	}
	$parts = explode('rmo.pkl.mentorg.com',$url);
	if(count($parts)>1)
	{
		return \Redirect::route('rmo.'.$param1, []);
	}
	
    return view('welcome');
});

Route::get('/', function () {
    return view('default');
});
