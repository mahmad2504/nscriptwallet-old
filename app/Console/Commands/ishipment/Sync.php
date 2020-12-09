<?php

namespace App\Console\Commands\ishipment;

use Illuminate\Console\Command;
use App\Apps\ishipment\ishipment;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'ishipment:sync {--rebuild=0} {--force=0} {--email_resend=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'International shipment sync';

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
		$app = new Ishipment($this->option());
		$app->Run();
    }
}
