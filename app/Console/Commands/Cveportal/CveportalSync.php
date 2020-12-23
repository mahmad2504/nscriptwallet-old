<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use Artisan;
class CveportalSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:sync {--rebuild=0} {--force=0} {--email=2}';

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
		Artisan::call('product:sync', ['--force' => 1]);
		Artisan::call('svm:sync', ['--force' => 1]);
		Artisan::call('nvd:sync', ['--force' => 1]);
		
    }
}