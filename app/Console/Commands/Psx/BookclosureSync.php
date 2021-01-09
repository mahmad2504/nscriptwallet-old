<?php
namespace App\Console\Commands\Psx;

use Illuminate\Console\Command;
use App\Apps\Psx\Bookclosure;

class BookclosureSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psx:bookclosure:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PSX Bookclosure results to telegram';

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
		
		$app = new Bookclosure($this->option());
		$app->Run();
    }
}