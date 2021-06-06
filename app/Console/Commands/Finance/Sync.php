<?php
namespace App\Console\Commands\Finance;

use Illuminate\Console\Command;
use App\Apps\Finance\Finance;
use App\Email;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finance:sync {--rebuild=0} {--force=0} {--email=2} {--dbname=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Description';

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
		
		$app = new Finance($this->option());
		$app->Run();
    }
}