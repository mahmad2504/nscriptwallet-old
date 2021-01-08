<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Product;

class Cvestatus extends App{
	public $timezone='Asia/Karachi';
	public $options = 0;
	public $scriptname = "cvestatus";
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
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
	}
	public function Rebuild()
	{
		//$this->db->monitoring_lists->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	public function UpdateStatus($status)
	{
		$this->InitDb();
		$collection = $this->collectionname;
		$this->db->$collection->updateOne(
            [
				'status.cve'=>$status['cve'],
				'status.productid'=>$status['productid']
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
		$this->InitDb();
		$collection = $this->cvestatus;
		$record = $this->db->$collection->findOne(
			[
				'status.cve'=>$cve,
				'status.productid'=>$productid
			]	
		);
		if($record == null)
		{
			
			$ret = new \StdClass();
			$ret->cve=$cve;
			$ret->productid=$productid;
			$ret->state='Investigate';
			$ret->publish=false;
			return $ret;
		}
		return $record->status;
	}
}