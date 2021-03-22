<?php

namespace App\Http\Controllers\Cryptography;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Cryptography\Cryptography;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class CryptController extends Controller
{
	public function showproduct(Request $request,$product)
	{
		$app = new Cryptography();
		$project = $app->ReadProject(null,$product);
		if($project == null)
			dd('Project not found');
		
		if($request->package == null)
		{
			$project_name = strtoupper($project->name);
			return view('cryptography.product',compact('project','project_name'));
		}
		else
		{
			foreach($project->packages as $package)
			{
				if($package->name == $request->package)
				{
					$package_name = $package->name;
					return view('cryptography.package',compact('package','package_name','project'));
				}
			}
		}
	}
	public function showfile(Request $request,$project,$file_identifier)
	{
		$app = new Cryptography();
		$project = $app->ReadProject(null,$project);
		dd($project);
		$hits = $app->ReadHits(null,null,$file_identifier);
		if(count($hits)==0)
			dd('Not Found');
		$file_name = $hits[0]->file;
		return view('cryptography.file',compact('hits','file_name'));
		
	}
}
