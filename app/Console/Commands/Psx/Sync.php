<?php
namespace App\Console\Commands\Psx;

use Illuminate\Console\Command;
use App\Apps\Psx\Psxannouncement;

class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psxannouncement:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PSX Announcements to telegram';

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
		
		$app = new Psxannouncement($this->option());
		$app->Run();
    }
}