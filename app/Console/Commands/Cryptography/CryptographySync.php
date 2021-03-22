<?php
namespace App\Console\Commands\Cryptography;

use Illuminate\Console\Command;
use App\Apps\Cryptography\Cryptography;
use App\Email;
class CryptographySync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cryptography:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';
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
		
		$app = new Cryptography($this->option());
		$app->Run();
    }
}