<?php
namespace App\Apps\Ishipment;
use App\Apps\App;

class Ishipment extends App{
	public $timezone='Asia/Karachi';
	public $board = '5a78b043543acc40d8ba06f9';
	public $lists = ["Upcoming"=>"5aa212ebaac901de308b610c","Shipment"=>"5a78b08798546b40f68be6ee","Custom"=>"5a7c3ccdb5181394a42a0b06","Expense"=>"5a78b08c6f85c304e464aa07","Archive"=>"5fc5d63ded63c36a03facf6"];
		
	public function __construct()
    {
		$server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		date_default_timezone_set($this->timezone);
		parent::__construct(__NAMESPACE__, $server);
    }
	public function Permission($update_every_xmin=0)
	{
		return parent::Permission($update_every_xmin);
	}
	function ReadCard($id)
	{
		$query=['id'=>$id];
		$obj = $this->db->cards->findOne($query);
		if($obj == null)
			return null;
		$obj =  $obj->jsonSerialize();
		unset($obj->_id);
		return $obj;
	}
	function SaveCard($card)
	{
		$query=['id'=>$card->id];
		$options=['upsert'=>true];
		$this->db->cards->updateOne($query,['$set'=>$card],$options);
	}
	public function ReadActive()
	{
		$active =  $this->MongoRead('cards',['list'=> ['$nin' =>['Expense']],'archived'=>['$ne'=>1]],['dateLastActivity' => -1],[]);
		$date = new \DateTime('-6 days');
		$closed = $this->MongoRead('cards',['list'=>'Expense','dayLastActivity'=>['$gt' => $date->format('Y-m-d')]  ],['dateLastActivity' => -1],[]);
	    return array_merge($active->toArray(),$closed->toArray());
	}
}