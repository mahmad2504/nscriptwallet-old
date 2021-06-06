<?php

namespace App\Http\Controllers\Cryptography;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Cryptography\Cryptography;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class CryptController extends Controller
{
	public function showfile(Request $request,$project,$file_identifier)
	{
		$app = new Cryptography();
		$project = $app->ReadProject(null,$project);
		$hits = $app->ReadHits(null,null,$file_identifier);
		if(count($hits)==0)
			dd('Not Found');
		$evidence = [];
		foreach($hits as $hit)
			$evidence[$hit->line_number] = $hit->evidence_type;
		$file_name = $hits[0]->location[0];
		
		$handle = fopen($file_name, "r");
		if ($handle) 
		{
			$line_number = 1;
			$file_data = [];
			while (($line = fgets($handle)) !== false) 
			{
				// process the line read.
				$line = str_replace("\n","",$line);
				$evd = null;
				if(isset($evidence[$line_number]))
					$evd = $evidence[$line_number];
					
				if($evd != null)
				{
					$file_data[] = 'HIT ['.$evd.']';
					$file_data[] = $line_number++."   ".$line;
				}
				else
					$file_data[] =  $line_number++."   ".$line;
			}
			fclose($handle);
			//dd($file_data);
			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary"); 
			header("Content-disposition: attachment; filename=\"" . basename($file_name) . "\"");
			echo readfile($file_name);
			//return view('cryptography.file',compact('file_data','file_name'));
		} 
		else 
		{
			// error opening the file.
		} 
	}
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
	public function triage(Request $request)
	{
		$app = new Cryptography();
		$triage = $app->ReadTriage($request->id);
		$triage->text = $request->text;
		$triage->comment = $request->comment;
		if($triage->text == 'Valid')
		{
			$triage->suspicious = 1;
			$triage->triaged = 1;
		}
		else if($triage->text == 'Ignore')
		{
			$triage->suspicious = 0;
			$triage->triaged = 1;
		}
		else
		{
			$triage->suspicious = 0;
			$triage->triaged = 0;
		}	
		$app->SaveTriage($triage);
		$hit = $app->ReadHit($request->id);
		foreach($hit->file_identifier as $file_identifier)
			$app->UpdateProjects($file_identifier);
			
		
	}
	public function showhits(Request $request,$project,$file_identifier)
	{
		$package = $request->package;
		
		$app = new Cryptography();
		
		$project = $app->ReadProject(null,$project);
		$hits = $app->ReadHits(null,null,$file_identifier);
		
		if(count($hits)==0)
			dd('Not Found');
		if($package == null)
			$package = $hits[0]->package[0];
		
		foreach($hits as $hit)
		{
			$triage = $app->ReadTriage($hit->id);
			$hit->triage = $triage;
		}
		$file_name = $hits[0]->file;
		return view('cryptography.hits',compact('hits','file_name','project','file_identifier','package'));
	}
	public function Search($project,$package,$hitid)
	{
		$app = new Cryptography();
		$hit = $app->ReadHit($hitid);
		if($hit == null)
			dd('Not Found');
		
		//dump($project);
		//dump($package);
		//dump($hitid);
		$hits = $app->SearchHits($package,$hit->line_text,$hit->line_text_after_1,$hit->line_text_after_2,$hit->line_text_after_3);
		//dd($hits);
		if(count($hits)==0)
		    dd('Not Found');
		
		foreach($hits as $hit)
		{
			$triage = $app->ReadTriage($hit->id);
			$hit->triage = $triage;
		}
		$file_name = $hits[0]->file;
		return view('cryptography.searchhits',compact('hits','project','package'));
		
	}
}
