<?php

namespace App\Console\Commands\Sample;

use Illuminate\Console\Command;
use App\Apps\Sample\Sample;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'sample:sync {--rebuild=0} {--force=0} {--beat=0}';

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
		$this->app = new Sample();
		$this->rebuild = $this->option('rebuild');
		$sync_requested = $this->app->Read('sync_requested');
		$this->force = $this->option('force');
		if(($this->rebuild == 1)||($this->force)||$sync_requested)
			return true;
		return $this->app->Permission();		
	}
	public function Preprocess()
	{
		
	}
	public function Postprocess()
	{
		$this->app->SaveUpdateTime();
		$this->app->Save(['sync_requested'=>0]);
	}
    public function handle()
    {
		if(!$this->Permission())
		{
			echo "Not permitted at this time\n";
			return;
		}
		$app = $this->app;
		$this->Preprocess();
		$this->Postprocess();
    }
}
