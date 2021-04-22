<?php
namespace App\Console\Commands\Psx;

use Illuminate\Console\Command;
use App\Apps\Psx\Server;

class GraphSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'psx:graph:sync {--rebuild=0} {--force=0} {--email=2} {--email_resend=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'PSX Bullish indicators';

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
		//$app = new Server($this->option());
		//$app->Run();
		$data = file_get_contents('http://www.scstrade.com/stockscreening/SS_BasicChart.aspx?symbol=UBL');
		$data = explode('cacheDefeat=',$data)[1];
		$data = explode('"',$data)[0];
		
		dd('http://www.scstrade.com/stockscreening/SS_BasicChart.aspx?symbol=UBL&ChartDirectorChartImage=chart_ContentPlaceHolder1_WebChartViewer1&cacheDefeat='.$data);
		
		
    }
}