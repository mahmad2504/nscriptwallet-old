<?php
namespace App\Console\Commands\Bspestimate;

use Illuminate\Console\Command;
use App\Apps\Bspestimate\Bspestimate;
use App\Email;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bspestimate:sync {--rebuild=0} {--force=0} {--email=2}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update BSP  driver strings';

    /**
     * Create a new command instance.
     *
     * @return void
     */
	
    public function __construct()
    {
		parent::__construct();
    }
	
    public function handle()
    {
		$app = new Bspestimate($this->option());
		$app->Run();
    }
}