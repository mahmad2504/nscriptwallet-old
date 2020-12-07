<?php
namespace App\Console\Commands\Epicupdate;

use Illuminate\Console\Command;
use App\Apps\Epicupdate\Epicupdate;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'epicupdate:sync {--rebuild=0} {--force=0} {--email=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates estimates and work on epics';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
	
    public function handle()//
    {
		$app = new Epicupdate($this->option());
		$app->Run();
    }
}