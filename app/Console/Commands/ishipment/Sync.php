<?php

namespace App\Console\Commands\ishipment;

use Illuminate\Console\Command;
use App\Libs\Trello\Trello;
use App\Apps\ishipment\ishipment;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'ishipment:sync {--rebuild=0} {--force=0} {--beat=0}';

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
		$this->app = new Ishipment();
		$this->rebuild = $this->option('rebuild');
		$this->force=$this->option('force');
		if(($this->rebuild == 1)||($this->force))
			return true;
		return $this->app->Permission(10); //update every 2 min;
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
    public function handle()
    {
		if(!$this->Permission())
		{
			return;
		}
		
		/////////////////////////////////////////////////////////////////////
		$this->trello = new Trello(env("TRELLO_KEY"),env("TRELLO_TOKEN"));
		$this->Preprocess();
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
				else if(($card->dateLastActivity != $scard->dateLastActivity)||($this->rebuild))
				{
					echo "Processing ticket $inprocess/$total ".$card->id."\n";
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
