<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Product;
use App\Email;
class ProductSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:product:sync {--rebuild=0} {--force=0} {--email=2} {--organization=default}';

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
	
    public function handle()
    {
		$app = new Product($this->option(),null);
		$app->Run();
    }
}