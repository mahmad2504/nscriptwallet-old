<?php
namespace App\Console\Commands\Pullrequest;

use Illuminate\Console\Command;
use App\Apps\Pullrequest\Pullrequest;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'pullrequest:sync {--rebuild=0} {--force=0} {--email=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Pull Request notifications';

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
		$app = new Pullrequest($this->option());
		$app->Run();
    }
}