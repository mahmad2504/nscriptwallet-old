<?php
namespace App\Console\Commands\Sample;

use Illuminate\Console\Command;
use App\Apps\Sample\Sample;
use App\Email;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sample:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

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
		
		$app = new Sample($this->option());
		$app->Run();
    }
}