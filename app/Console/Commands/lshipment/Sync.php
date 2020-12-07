<?php

namespace App\Console\Commands\lshipment;

use Illuminate\Console\Command;
use App\Apps\lshipment\lshipment;
class Sync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
protected $signature = 'Lshipment:sync {--rebuild=0} {--force=0} {--beat=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Local shipment sync';

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
		$app = new Lshipment($this->option());
		$app->Run();
    }
}
