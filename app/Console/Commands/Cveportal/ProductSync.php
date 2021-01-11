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
    protected $signature = 'cveportal:product:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

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
		$data = file_get_contents("app/Console/Commands/Cveportal/products.json");
		$data = preg_replace('/\s+/', ' ', trim($data));
		//$data = json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $data), false );
		$data = json_decode(utf8_encode($data));
		$app = new Product($this->option(),$data);
		$app->Run();
    }
}