<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use \MongoDB\BSON\Regex;

class Cache extends Cveportal{
	public $scriptname = 'cveportal:cache';
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=1)
	{
		return true;
	}

	public function Rebuild()
	{
		//$this->db->products->drop();
		//$this->options['email']=0;// no emails when rebuild
	}
	function Clean()
	{
		$scriptname = $this->scriptname;
		$this->db->$scriptname->drop();
		dump("Cache cleaned");
	}
	function Get($key)
	{
		$scriptname = $this->scriptname;
		$query=['key'=>$key];
		$obj = $this->db->$scriptname->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		return $obj->data;
	}
	function Put($key,$data)
	{
		$o =  new \StdClass();
		$o->key=$key;
		$o->data = $data;
		
		$scriptname = $this->scriptname;
		$query=['key'=>$o->key];
		$options=['upsert'=>true];
		$this->db->$scriptname->updateOne($query,['$set'=>$o],$options);
	}
}