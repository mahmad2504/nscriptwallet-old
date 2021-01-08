<?php

namespace App\Http\Controllers\Cveportal;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Apps\Cveportal\Product;
use Redirect,Response, Artisan;
use Carbon\Carbon;
class CveportalController extends Controller
{
	public function Index(Request $request)
	{
		$p = new Product();
		$group_names = $p->GetGroupNames();
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetProductNames($group_name);
			foreach($productnames as $productname)
			{
				$version_names[] = $p->GetVersionNames($group_name,$productname);
			}
			$product_names[] = $productnames;
		}
		$refresh = 0;
		return view('cveportal.index',compact('group_names','product_names','version_names','refresh'));
	}
	public function GetCves(Request $request,$group='all',$product='all',$version='all',$admin='all')
	{
		$static_file_name = $group."_".$product."_".$version;
		ob_start('ob_gzhandler');
		$p = new Product();
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		$admins = $admin=='all'?null:$admin;
		$ids = $p->GetIds($group,$product,$version,$admin);
		dd($ids);
		sort($ids);
		$key = md5(implode(",",$ids));
		$key = $key.'published';
		$data = null;
		if(($request->refresh==null)||($request->refresh==0))
			$data = Cache::Load($key);
		
		if($data==null)
		{
			$c =  new CVE();
			$data = $c->GetPublished($ids);
			Cache::Save($key,json_encode($data));
			//Cache::SaveStaticPage($static_file_name,json_encode($data));
		}
		return $data;
	}
}