<?php

namespace App\Console\Commands\lshipment;

use Illuminate\Console\Command;
use App\Libs\Trello\Trello;
use App\Apps\lshipment\lshipment;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'Lshipment:sync {--rebuild=0} {--force=0} {--beat=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
	public function Permission()
	{
		$this->app = new Lshipment();
		$this->rebuild = $this->option('rebuild');
		$this->force = $this->option('force');
		if(($this->rebuild == 1)||($this->force))
			return true;
		return $this->app->Permission(10); //update every 10 min;
	}
    
	public function Preprocess()
	{
		
	}
	public function Postprocess()
	{
		$this->app->SaveUpdateTime();

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
    public function handle()
    {
		if(!$this->Permission())
		{
			return;
		}
		$this->Preprocess();
		/////////////////////////////////////////////////////////////////////
		$this->trello = new Trello(env("TRELLO_KEY"),env("TRELLO_TOKEN"));
		//$lists = $board = $this->trello->Lists($this->app->board);
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
					$card->list = $name;
					$this->UpdateCard($card,$name);
					$this->app->SaveCard($card);
				}
				else if(($card->dateLastActivity != $scard->dateLastActivity)||($this->rebuild))
				{
					echo "Processing ticket $inprocess/$total ".$card->id."\n";
					$card->list = $name;
					$this->UpdateCard($card,$name);
					$this->app->SaveCard($card);
				}
				$inprocess++;
			}
		}
		///////////////////////
		$this->Postprocess();
    }
}
