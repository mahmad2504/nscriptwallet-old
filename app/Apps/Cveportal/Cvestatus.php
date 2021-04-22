<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Product;

class Cvestatus extends Cveportal{
	public $timezone='Asia/Karachi';
	public $options = 0;
	public $scriptname = "cveportal:cvestatus";

	public function __construct($options=null)
    {

		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=10)
	{
		return true;
		return parent::TimeToRun($update_every_xmin);
	}
	
	public function Rebuild()
	{
		//$this->db->monitoring_lists->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	
	public function UpdateStatus($status)
	{
		if(is_array($status))
		{
			$s = new \StdClass();
			$s->cve = $status['cve'];
			$s->productid = $status['productid'];
			$s->triage = $status['triage'];
			$s->publish = $status['publish'];
			$s->source = $status['source'];
			$s->comment = $status['comment'];
			$status = $s;
		}
		$collection = $this->scriptname;
		$this->db->$collection->updateOne(
            [
				'status.cve'=>$status->cve,
				'status.productid'=>$status->productid
			],
            ['$set' => [
				'status' => $status,
				]
			],
            ['upsert' => true]
        );
	}
	public function GetStatus($cve,$productid)
	{
		$collection = $this->scriptname;
		$record = $this->db->$collection->findOne(
			[
				'status.cve'=>$cve,
				'status.productid'=>$productid
			]	
		);
		if($record == null)
		{
			$product = new Product();
			$ret = new \StdClass();
			$ret->cve=$cve;
			$ret->productid=$productid;
			$p = $product->GetProduct($ret->productid);
			
			$ret->triage=$this->default_triage_status;
			$ret->publish=$p->publish;
			$ret->source='manual';
			$ret->comment='';
			$this->UpdateStatus($ret);
			return $this->GetStatus($cve,$productid);
		}
		if(!isset($record->status->comment))
			$record->status->comment = '';
		
		return $record->status;
	}
	public function GetPublished($productid)
	{
		$collection = $this->scriptname;
		$records = $this->db->$collection->find(
			[
				'status.productid'=>$productid,
			]	
		);
		return $records->toArray();
	}
}