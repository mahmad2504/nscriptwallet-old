<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use \MongoDB\BSON\Regex;
use Aws\S3\S3Client;

class Staticpages extends Cveportal{
	public $scriptname = 'cveportal:static';
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=60)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		//$this->db->products->drop();
		//$this->options['email']=0;// no emails when rebuild
	}
	public function ProductData()
	{
		$p = new Product();
		$group_names = $p->GetGroupNames(null,"1","1");
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetProductNames($group_name,"1","1");
			foreach($productnames as $productname)
			{
				$version_names[] = $p->GetVersionNames($group_name,$productname,"1","1");
			}
			$product_names[] = $productnames;
		}
		$o = new \StdClass();
		$o->group_names = $group_names;
		$o->product_names = $product_names;
		$o->version_names = $version_names;
		
		return $o;
	}
	public function recurse_copy($src,$dst) 
	{ 
		$dir = opendir($src); 
		@mkdir($dst); 
		while(false !== ( $file = readdir($dir)) ) 
		{ 
			if (( $file != '.' ) && ( $file != '..' )) 
			{ 
				if ( is_dir($src . '/' . $file) ) 
				{ 
					$this->recurse_copy($src . '/' . $file,$dst . '/' . $file); 
				} 
				else 
				{ 
					copy($src . '/' . $file,$dst . '/' . $file); 
				}	 
			} 
		} 
		closedir($dir); 
	} 
	public function GetCves($group='all',$product='all',$version='all')
	{
		$filename = $group."_".$product."_".$version.".json";
		$p = new Product();
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		$ids = $p->GetIds($group,$product,$version,null,"1","1");
		sort($ids);
		$key = md5(implode(",",$ids));
		$c =  new CVE();
		$data = $c->GetPublished($ids);
		file_put_contents('data/cveportal/static/data/'.$filename,json_encode($data));
		$this->Publish(json_encode($data),$filename);
		return $data;
	}
	public function Publish($data,$filename)
	{
		dump($filename);
		$s3Client = new S3Client([
			//'profile' => 'default',
			'region' => 'us-west-2',
			'version'     => 'latest',
			'credentials' => [
				'key'    => env('AWS_KEY'),
				'secret' => env('AWS_SECRET'),
			]
		]);
		$result = $s3Client->putObject([
        'Bucket' => env('AWS_URL'),
        'Key'    => 'cveportal/data/'.$filename,
        'Body'   => $data,
        'ACL'    => 'public-read'
		]);
	}
	public function Script()
	{
		//$this->recurse_copy('app/Apps/Cveportal/staticpages','data/cveportal/static');
		//@mkdir('data/cveportal/static/data');
		$pdata = $this->ProductData();
		$this->Publish(json_encode($pdata),'product.json');
		file_put_contents('data/cveportal/static/data/product.json',json_encode($pdata));
		$this->GetCves('all','all','all');
		$i = 0;
		$j=0;
		foreach($pdata->group_names as $group)
		{
			$this->GetCves($group,'all','all');
			$products = $pdata->product_names[$i];
			
			foreach($products as $product)
			{
				$this->GetCves($group,$product,'all');
				$versions = $pdata->version_names[$j];
				foreach($versions as $version)
				{
					$this->GetCves($group,$product,$version);
				}
				$j++;
			}
			$i++;
		}	
		//dd($this->GetCves('all','all','all'));
	}
}