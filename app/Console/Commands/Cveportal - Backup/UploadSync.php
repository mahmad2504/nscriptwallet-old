<?php
namespace App\Console\Commands\Cveportal;

use Illuminate\Console\Command;
use App\Apps\Cveportal\Staticpages;
use App\Email;
class UploadSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cveportal:file:upload {--file=null} {--dest=null}';

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
		$options=$this->option();
		$data = file_get_contents($options['file']);
		
		$app = new Staticpages($options);
		$app->Upload2($data,$options['dest']);
		//if(
		//$app->Run();
    }
}