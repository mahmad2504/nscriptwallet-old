<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use \MongoDB\BSON\Regex;
use Aws\S3\S3Client;
use App\Apps\Cveportal\Instance;

class Staticpages extends Cveportal{
	public $scriptname = 'cveportal_staticpages';
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		
		$inst = Instance::Get($options['organization']);
		if($inst == null)
			dd('Instance not found');
		
		$this->s3bucket = $inst->s3bucket;
		parent::__construct($options);
    }
	public function InConsole($yes)
	{
		if($yes)
			$this->datafolder = 'data';
		else
			$this->datafolder = '../data';
	}
	public function TimeToRun($update_every_xmin=1440)
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
		$p = new Product($this->options);
		$group_names = $p->GetGroups(['external'=>'1']);
		$product_names = [];
		$version_names = [];
		foreach($group_names as $group_name)
		{
			$productnames = $p->GetNames(['group'=>$group_name,'external'=>'1']);
			foreach($productnames as $productname)
			{
				
				$version_names[] = $p->GetVersions(["group"=>$group_name,"name"=>$productname,'external'=>'1']);
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
		$p = new Product($this->options);
		
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		
		$ids = $p->Search($group,$product,$version);
		$c =  new CVE($this->options);
		$limit=100;
		$skip=0;
		$max=2000;
		if($version==null)
			$max = 200;
		$output = [];
		$cur_product_id = null;
		if($version != null)// there should be only 1 id
			$cur_product_id = $ids[0];
			
		while(1)
		{
			$data = $c->Get($ids,$limit,$skip,$cur_product_id,[],1,1);
			unset($data['total']);
			if(count($data)==0)
				break;
			
			$count = 0;
			foreach($data as $d)
			{
				if($c->IsItInvalid($d))	
					continue;
				
				if($c->IsItPublished($d))
				{
					$output[] = $d;
					$count++;
				}	
							
				if($count >= $max)
				{
					dump('Truncating');
					break;
				}
			}
			$skip=$skip+100;
		}
		file_put_contents($this->CleanFileName($this->datafolder.'/cveportal/static/data/'.$filename),json_encode($output));
		$this->Publish(json_encode($data),$filename);
		return $data;
	}
	public function CleanFileName($filename)
	{
		return str_replace(":","_",$filename);
	}
	public function GetRssfeed($group='all',$product='all',$version='all',$productid=null)
	{
		$filename = $group."_".$product."_".$version.".xml";
		$p= new Product($this->options);
		$group = $group=='all'?null:$group;
		$product = $product=='all'?null:$product;
		$version = $version=='all'?null:$version;
		
		$ids = $p->Search($group,$product,$version);
		$c =  new CVE($this->options);
		$limit=100;
		$skip=0;
		$max=2000;
		if($version==null)
			$max = 200;
		$output = [];
		$cur_product_id = null;
		if($version != null)// there should be only 1 id
			$cur_product_id = $ids[0];
		

		while(1)
		{
			$data = $c->Get($ids,$limit,$skip,$cur_product_id);
			unset($data['total']);
			if(count($data)==0)
				break;
			
			$count = 0;
			foreach($data as $d)
			{
				if($c->IsItInvalid($d))	
					continue;
				
				if($c->IsItPublished($d))
				{
					$output[] = $d;
					$count++;
				}	
							
				if($count >= $max)
				{
					dump('Truncating');
					break;
				}
			}
			$skip=$skip+100;
		}
		$p = $p->GetProducts(['id'=>$ids[0]]);
		$p = $p[0];
		
		$PHP_EOL = PHP_EOL;
		
		$xml = '<rss version="2.0">'.$PHP_EOL;
		$xml .= "<channel>".$PHP_EOL;
		$xml .= "<title>CVE RSS Feed</title>".$PHP_EOL;
		$xml .= "<link></link>".$PHP_EOL;
		$xml .= "<description>".$p->name." ".$p->version."</description>".$PHP_EOL;
		$xml .= "<product>".$p->name."</product>".$PHP_EOL;
		$xml .= "<version>".$p->version."</version>".$PHP_EOL;
		$xml .= "<language>en-us</language>".$PHP_EOL;
		$i=0;
		foreach($output as $d)
		{
			$title = $d->cve;
			$description = $d->title;
			$priority =  $d->priority;
			$solution = $d->solution;
			$vectorString = $d->vector;
			
			$baseScore = $d->basescore;
			$triage = 'Not Applicable';
			if(isset($d->status))
				$triage = $d->status->triage;
		
			$xml .= "<item>".$PHP_EOL;
			$xml .= "<title>".$title."</title>".$PHP_EOL;
			$xml .= "<description><![CDATA[".$description."]]></description>".$PHP_EOL;
			$xml .= "<priority>".$priority."</priority>".$PHP_EOL;
			$xml .= "<vectorString>".$vectorString."</vectorString>".$PHP_EOL;
			$xml .= "<baseScore>".$baseScore."</baseScore>".$PHP_EOL;
			$xml .= "<triage>".$triage."</triage>".$PHP_EOL;
			$xml .= "<solution><![CDATA[".$solution."]]></solution>".$PHP_EOL;
			$xml .= "</item>".$PHP_EOL;
			$i++;
		}
		$xml .= "</channel>".$PHP_EOL;
		$xml .= "</rss>".$PHP_EOL;
		if($productid == null)
		{
			file_put_contents($this->CleanFileName($this->datafolder .'/cveportal/static/data/'.$filename),$xml);
			$this->Publish($xml,$filename);
		}
		return $xml;
		
	}
	public function Upload2($data,$dest)// dest  is 'cveportal/data/filename',
	{
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
        'Key'    => $dest,
        'Body'   => $data,
        'ACL'    => 'public-read'
		]);
	}
	public  function RemoveS3Folder()
	{
		dump("Removing  ".$this->s3bucket);
		exec("aws s3 rm ".$this->s3bucket." --recursive"); 
	}
	public function PublishFolder()
	{
		$cwd = getcwd();
		$cwd = $cwd."/data/cveportal/static";
		dump("Publishing on ".$this->s3bucket);
		exec("aws s3 sync ".$cwd." ".$this->s3bucket." --acl public-read-write");
	}
	public function Publish($data,$filename)
	{
		dump($filename);
		return;
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
		//$this->recurse_copy('data/cveportal/static','data/cveportal/tesstatic');
		//@mkdir('data/cveportal/static/data');
		$pdata = $this->ProductData();
		//$this->Publish(json_encode($pdata),'product.json');
		$this->EmptyDirectory($this->datafolder.'/cveportal/static/data');
		file_put_contents($this->datafolder.'/cveportal/static/data/product.json',json_encode($pdata));
		$this->GetCves('all','all','all');
		$this->GetRssfeed('all','all','all');
		$i = 0;
		$j=0;
		foreach($pdata->group_names as $group)
		{
			$this->GetCves($group,'all','all');
			$this->GetRssfeed($group,'all','all');
			$products = $pdata->product_names[$i];
			foreach($products as $product)
			{
				$this->GetCves($group,$product,'all');
				$this->GetRssfeed($group,$product,'all');
				$versions = $pdata->version_names[$j];
				foreach($versions as $version)
				{
					$this->GetCves($group,$product,$version);
					$this->GetRssfeed($group,$product,$version);
				}
				$j++;
			}
			$i++;
		}
		$meta = new \StdClass();
		$meta->updatedon='This page was last updated on '.gmdate("D, d M Y H:i:s", time())." GMT";
		if($this->options['rebuild']==1)
			$this->RemoveS3Folder();
		
		file_put_contents($this->datafolder .'/cveportal/static/data/meta.json',json_encode($meta));
		$this->PublishFolder();
		
	}
}