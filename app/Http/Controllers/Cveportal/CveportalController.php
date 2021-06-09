<?php

namespace App\Http\Controllers\Cveportal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Cveportal\Product;
use Redirect,Response, Artisan;
use Carbon\Carbon;
use App\Apps\Cveportal\Cve;
use App\Libs\Ldap\Ldap;
use App\Apps\Cveportal\Cvestatus;
use App\Apps\Cveportal\Staticpages;
use App\Apps\Cveportal\SVM;
use App\Apps\Cveportal\Instance;
class CveportalController extends Controller
{
	public function SyncRequest(Request $request)
	{
		if($request->organization == null)
			return Response::json(['error' => 'Invalid Organization'], 404);
		
		$inst = Instance::Get($request->organization);
		if($inst == null)
			return Response::json(['error' => 'Invalid Organization'], 404);
		
		if($request->svm != null)
		{
			$options['organization'] = $request->organization;
			$svm= new SVM($options); 
			if($request->svm == 0 )
			{
				$svm->ClearSyncRequest();
				return Response::json(['success' => 'SVM Sync Request cancelled'], 200);
		
			}
			else
			{
				$svm->RequestSync();
				return Response::json(['success' => 'SVM Sync Requested'], 200);
			}
		}
		else if($request->staticpages != null)
		{
			$options['organization'] = $request->organization;
			$sp= new Staticpages($options); 
			if($request->staticpages ==0 )
			{
				$sp->ClearSyncRequest();
				return Response::json(['success' => 'Static Pages Sync Request Cancelled'], 200);
			}
			else
			{
				$sp->RequestSync();
				return Response::json(['success' => 'Static Pages Sync Requested'], 200);
			}
		}
	}
	public function IsSyncRequested(Request $request)
	{
		if($request->organization == null)
			return Response::json(['error' => 'Invalid Organization'], 404);
		
		$inst = Instance::Get($request->organization);
		if($inst == null)
			return Response::json(['error' => 'Invalid Organization'], 404);
		
		if($request->svm != null)
		{
			$options['organization'] = $request->organization;
			$svm= new SVM($options); 
			if($svm->IsSyncRequested()==1)
				return Response::json(['data' => 1], 200);
			return Response::json(['data' => 0], 200);
		}
		else if($request->staticpages != null)
		{
			$options['organization'] = $request->organization;
			$sp= new Staticpages($options); 
			if($sp->IsSyncRequested()==1)
				return Response::json(['data' => 1], 200);
			return Response::json(['data' => 0], 200);
		}
	}
	public function Index(Request $request)
	{
		$data = $request->session()->get('data');
		$organization='default';
		if($data != null)
			if(isset($data->organization))
				$organization = $data->organization;
		
		if($request->organization != null)
			$organization = $request->organization;
		$inst = Instance::Get($organization);
		if($inst == null)
		{
			return 'Page Not Found';
		}
		$options['organization'] = $organization;
		$svm= new SVM($options);

		$dt =  new Carbon('now');
		$last_updated = '-';
		if($svm->ReadUpdateTime() != null)
		{
			$dt->setTimeStamp(strtotime($svm->ReadUpdateTime()));
			$dt->setTimezone(new \DateTimeZone('UTC'));
			$last_updated= $dt->format("D M Y j G:i:s T");
		}
		$p = new Product($options);
		
		$group_names = $p->GetGroups();
		
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetNames(["group"=>$group_name]);
			foreach($productnames as $productname)
			{
				$version_names[] = $p->GetVersions(["group"=>$group_name,"name"=>$productname]);
			}
			$product_names[] = $productnames;
		}
		$organization_name = strtoupper($inst->name);
		return view('cveportal.index',compact('organization_name','organization','group_names','product_names','version_names','last_updated'));
	}
	public function Triage(Request $request)
	{
		$data = $request->session()->get('data');
		if($data == null)
			return view('cveportal.login');
		if(!isset($data->user_name)||!isset($data->organization))
			return view('cveportal.login');
		
		$inst = Instance::Get($data->organization);
		if($inst == null)
		{
			return 'Page Not Found';
		}
		$options['organization'] = $data->organization;
		
		$svm= new SVM($options);
		
		$dt =  new Carbon('now');
		$dt->setTimeStamp(strtotime($svm->ReadUpdateTime()));
		$dt->setTimezone(new \DateTimeZone('UTC'));
		$last_updated= $dt->format("D M Y j G:i:s T");
		
		$sp =  new StaticPages($options);
		$p = new Product($options);
		
		$group_names = $p->GetGroups(['admin'=>$data->user_name]);
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetNames(['group'=>$group_name]);
			foreach($productnames as $productname)
			{
				$version_names[] = $p->GetVersions(["group"=>$group_name,"name"=>$productname]);
			}
			$product_names[] = $productnames;
		}
		$displayname=$data->user_displayname;
		$admin = $data->user_name;
		$organization = $data->organization;
		$organization_name = strtoupper($inst->name);
	
		$svmsyncrequest = $svm->IsSyncRequested();
		
		if($svmsyncrequest != 1)
			$svmsyncrequest = 0;
		
		$publishrequest = $sp->IsSyncRequested();
		if($publishrequest  != 1)
			$publishrequest = 0;
	
		return view('cveportal.triage',compact('publishrequest','svmsyncrequest','organization_name','organization','displayname','group_names','product_names','version_names','admin','last_updated'));
	}
	public function Login(Request $request)
	{
		return view('cveportal.login');
	}
	public function Logout(Request $request)
	{
		$request->session()->forget('data');
		return redirect()->route('cveportal.login');
		//echo "Your are logged out of system";
	}
	public function Authenticate(Request $request)
	{
		if($request->getMethod() == 'GET')
		{
			$user = $request->user;
			$password = $request->password;
			$organization = $request->organization;
		}
		else
		{
			if(!isset($request->data['user'])||!isset($request->data['password'])||!isset($request->data['organization']))
				return Response::json(['error' => 'Invalid Credentials'], 404); 
			$user = $request->data['user'];
			$password = $request->data['password'];
			$organization = $request->data['organization'];
		}
		$instance = Instance::Get($organization);
		
		if($instance == null)
			return Response::json(['error' => 'Invalid Organization'], 404);
		
		$ldap =  new Ldap();
		$data = $ldap->Login($user,$password);
		
		if($data== null)
		{
			$request->session()->forget('data');
			return Response::json(['error' => 'Invalid Credentials'], 404); 
		}
		else
		{
			$data->organization = $organization;
			$request->session()->put('data', $data);
		}
		
		//dump("Success");
		return [];
		//return $data->user_displayname;
	}
	public function GetCves(Request $request,$group='all',$product='all',$version='all',$admin='all',$organization='default')
	{
		$inst = Instance::Get($organization);
		if($inst == null)
		{
			return [];
		}
		
		$options['organization'] = $organization;
		
		$svm= new SVM($options);
		
		$cve =  new CVE($options);
		$limit = 0;
		$skip = 0;
		$severity=null;
		if($request->page!=null)
		{
			$skip = $request->size*($request->page-1);
			$limit= $request->size*1;
		}
		$p = new Product($options);
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		$admin = $admin=='all'?null:$admin;
		
		$ids = $p->Search($group,$product,$version,$admin);
		
		$output= [];
		if(count($ids)==0)
		{
			$output['total']=$total;
			$output['data']= [];
			return $output;
		}
		$admin_ids = $p->Search(null,null,null,$admin);
		
		$cur_product_id = null;
		if($version != null)// there should be only 1 id
			$cur_product_id = $ids[0];
		
		if($admin==null)
		{
			$data = $cve->Get($ids,$limit,$skip,$cur_product_id,$admin_ids,1);
		}
		else
			$data = $cve->Get($ids,$limit,$skip,$cur_product_id,$admin_ids);
		$total = $data['total'];
		unset($data['total']);
		$output['total']=$total;
		$output['organization']=$organization;
		$output['page_size']=$request->size*1;	
		$output['last_index']=$skip+$output['page_size'];
		$output['last_page'] = -1;
		if($request->size > 0)
			$output['last_page']= ceil($total/($request->size*1));
		$output['data']= array_values($data);
		
		return $output;
	}
	public function StatusUpdate(Request $request)
	{
		$inst = Instance::Get($request->organization);
		if($inst == null)
		{
			dd($organization." is invalid organization");
		}
		$options['organization'] = $request->organization;
		$p = new Product($options);
		$cvestatus = new CVEStatus($options);
		$cvestatus->UpdateStatus($request->status);
		$status = $cvestatus->GetStatus($request->status['cve'],$request->status['productid']);
		return $status ;
	}
}