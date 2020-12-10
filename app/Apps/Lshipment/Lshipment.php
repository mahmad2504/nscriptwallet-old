<?php
namespace App\Apps\Lshipment;
use App\Apps\App;
use App\Libs\Trello\Trello;

class Lshipment extends App{
	public $timezone='Asia/Karachi';
	public $board = '5e96d769cab6ce1d5e4fdb91';
	public $lists = ["List 1"=>"5e96d7c8ebdb461cc84f83ba","List 2"=>"5e96d7a0dffcf41ccac595c6","Archive"=>"5fc5d45fe9fe3702e6e71433"];
		
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		$this->trello = new Trello(env("TRELLO_KEY"),env("TRELLO_TOKEN"));
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=10)
	{
		return parent::TimeToRun($update_every_xmin);
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
	public function UpdateCard($card)
	{
		if(
			($card->idList == $this->lists["List 1"])||
			($card->idList == $this->lists["List 2"])
		)
		{
			$card->archived=0;
		}
		else
		{
			$card->archived=1;
			return;
		}
		
		$card->archived=0;	
		$data = $this->trello->Card($card->id,'name,badges,desc,labels,url,dueComplete,idChecklists,idList');
		$data->checkitems = [];
		
		if(count($data->idChecklists)>0)
		{
			foreach($data->idChecklists as $id)
			{
				if(isset($this->checklists[$id]))
					$checklist_data = $this->checklists[$id];
				else
					$checklist_data = $this->trello->Checklist($id);
				foreach($checklist_data->checkItems as $checkItem)
				{
					$data->checkitems[$checkItem->name]=$checkItem->state;
				}
				break;
			}
		}
		$card->name = $data->name;
		$card->desc = $data->desc;
		$card->dueComplete = $data->dueComplete;
        $card->checkitems =   $data->checkitems;
		if(($card->dueComplete)||(!isset($card->createdon)))
		{
			$actions = $this->trello->GetActions($card->id,'updateCard');
			foreach($actions as $action)
			{
				if(isset($action->data->card->dueComplete))
					if($action->data->card->dueComplete === true)
						$card->deliveredon = explode("T",$action->date)[0];
			}	
			if(isset($action))
				$card->createdon = explode("T",$action->date)[0];
		}
		if($card->dueComplete == false)
			$card->deliveredon = '';
		
		$card->checkItems = $data->badges->checkItems;
		$card->checkItemsChecked = $data->badges->checkItemsChecked;
		$card->url = $data->url;
		$card->due = explode("T",$data->badges->due)[0];
		$card->progress = ($card->checkItemsChecked/$card->checkItems)*100;
		$card->labels = $data->labels;
		
	}
	public function Rebuild()
	{
		dump("Dropping cards database");
		$this->db->cards->drop();
	}
    public function Script()
    {
		//$lists = $board = $this->trello->Lists($this->app->board);
		//dd($lists);
		$board = $this->trello->Board($this->app->board);
		$cards = $this->trello->ListCardsOnBoard($board->id);
		$closedcards = $this->trello->ListClosedCardsOnBoard($board->id);
		foreach($closedcards as $card)
		{
			$card->idList = -1;
		}
		$cards = array_merge($cards,$closedcards);
		echo "Updating ".$board->name."\n";
		$total = count($cards);
		$inprocess = 1;
		foreach($cards as $card)
		{
			$card->dayLastActivity = explode("T",$card->dateLastActivity)[0];
			$scard = $this->app->ReadCard($card->id);
			if($scard == null)
			{
				echo "Processing ticket $inprocess/$total ".$card->id."\n";
				$this->UpdateCard($card);
				$this->app->SaveCard($card);
			}
			else if(($card->dateLastActivity != $scard->dateLastActivity)||($this->options['rebuild']))
			{
				echo "Processing ticket $inprocess/$total ".$card->id."\n";
				$this->UpdateCard($card);
				$this->app->SaveCard($card);
			}
			$inprocess++;
		}
	}
}