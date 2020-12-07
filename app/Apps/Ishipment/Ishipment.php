<?php
namespace App\Apps\Ishipment;
use App\Apps\App;
use App\Libs\Trello\Trello;

class Ishipment extends App{
	public $timezone='Asia/Karachi';
	public $board = '5a78b043543acc40d8ba06f9';
	public $lists = ["Upcoming"=>"5aa212ebaac901de308b610c","Shipment"=>"5a78b08798546b40f68be6ee","Custom"=>"5a7c3ccdb5181394a42a0b06","Expense"=>"5a78b08c6f85c304e464aa07","Archive"=>"5fc5d63ded63c36a03facf6"];
	//public $query='';
	//public $jira_fields = []; 
    //public $jira_customfields = [];  	
	//public $jira_server = 'EPS';
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
		$active =  $this->MongoRead('cards',['list'=> ['$nin' =>['Expense']],'archived'=>['$ne'=>1]],['dateLastActivity' => -1],[]);
		$date = new \DateTime('-6 days');
		$closed = $this->MongoRead('cards',['list'=>'Expense','dayLastActivity'=>['$gt' => $date->format('Y-m-d')]  ],['dateLastActivity' => -1],[]);
	    return array_merge($active->toArray(),$closed->toArray());
	}
	public function UpdateCard($card,$listname)
	{
		$card->list = $listname;
		if($listname == 'Archive')
		{
			$card->archived=1;
			return;
		}
		$card->archived=0;		
		$data = $this->trello->Card($card->id,'name,badges,desc,labels,url,dueComplete,idChecklists,idList');
		$attachements = $this->trello->Attachment($card->id);
		$data->trackingno = '';
		foreach($attachements as $attachement)
		{
			$parts = explode('Tracking Number: #',$attachement->name);
			if(count($parts)==2)
				$data->trackingno = $parts[1];
		}
		$data->checkitems = [];
		if(count($data->idChecklists)>0)
		{
			foreach($data->idChecklists as $id)
			{
				if($id = '5db286ad4973fc041fe623ba')
				{
					if(isset($this->checklists[$id]))
						$checklist_data = $this->checklists[$id];
					else
						$checklist_data = $this->trello->Checklist($id);
					foreach($checklist_data->checkItems as $checkItem)
					{
						$data->checkitems[$checkItem->name]=new \StdClass();
					}
				}
			}
		}
		if($data->badges->checkItemsChecked > 0)
		{
			$actions = $this->trello->GetActions($card->id,'updateCheckItemStateOnCard');
			foreach($actions as $action)
			{
				if(isset($action->data->checkItem->name))
				{
					if(isset($data->checkitems[$action->data->checkItem->name]))
					{   if(!isset($data->checkitems[$action->data->checkItem->name]->state))
						{
							$dt = explode("T",$action->date)[0];
							$data->checkitems[$action->data->checkItem->name]->state = $action->data->checkItem->state;
							$data->checkitems[$action->data->checkItem->name]->date = $dt;
						}
					}
				}
			}	
		}
		$card->trackingno = $data->trackingno;
		$card->name = $data->name;
		$card->desc = $data->desc;
		$card->dueComplete = $data->dueComplete;
        $card->checkitems =   $data->checkitems;
		$card->url = $data->url;
		$card->labels = $data->labels;
	}
    public function Script()
    {
		//$lists = $this->trello->Lists($this->app->board);
		//dd($lists);
		$board = $this->trello->Board($this->app->board);
		echo "Updating ".$board->name."\n";
		foreach($this->app->lists as $name=>$listid)
		{
			echo "Processing List ".$name."\n";
			
			$cards = $this->trello->ListCards($listid);
			if(!is_array($cards))
				continue;
				
			$total = count($cards);
			$inprocess = 1;
			foreach($cards as $card)
			{
				$card->dayLastActivity = explode("T",$card->dateLastActivity)[0];
				$scard = $this->app->ReadCard($card->id);
				if($scard == null)
				{
					echo "Processing ticket $inprocess/$total ".$card->id."\n";
					$this->UpdateCard($card,$name);
					$this->app->SaveCard($card);
				}
				else if(($card->dateLastActivity != $scard->dateLastActivity)||($this->options['rebuild']))
				{
					echo "Processing ticket $inprocess/$total ".$card->id."\n";
					$this->UpdateCard($card,$name);
					$this->app->SaveCard($card);
				}
				$inprocess++;
			}
		}
    }
}