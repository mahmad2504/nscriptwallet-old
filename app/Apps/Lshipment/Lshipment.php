<?php
namespace App\Apps\Lshipment;
use App\Apps\App;

class Lshipment extends App{
	public $timezone='Asia/Karachi';
	public $board = '5e96d769cab6ce1d5e4fdb91';
	public $lists = ["List 1"=>"5e96d7c8ebdb461cc84f83ba","List 2"=>"5e96d7a0dffcf41ccac595c6","Archive"=>"5fc5d45fe9fe3702e6e71433"];
		
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
		$date = new \DateTime('-7 days');
		$query =['$or' => [ ['progress' => ['$ne' =>100]],['due' => ['$gt' => $date->format('Y-m-d')]]],'archived'=>['$ne'=>1]];
		//$query =['dayLastActivity' => ['$gt' => '2020-07-01']];

		$records = $this->MongoRead('cards',$query,['due' => 1],[]);
		return $records->toArray();
	}
}