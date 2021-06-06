<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Product;
use Artisan;
class Instance {
	
	public function __construct()
    {
	}
	
	public static function Get($name)
	{
		switch($name)
		{
			case 'mentorgraphics_test':
				$data = new \StdClass();
				$data->producturl ='https://script.google.com/macros/s/AKfycbyFOMmy36I5Th0thXnwPBDkWQyvgIyuXS5ImiqifdnKZtGoHiMO8bmJ8zGE6kFefOos/exec';
				$data->s3bucket = 's3://eps.mentorcloudservices.com/embedded_test';
				$data->dbname = 'cveportal_test';
				$data->name = 'Mentor Graphics Test';
				break;
			case 'default':
			case 'mentorgraphics':
				$data = new \StdClass();
				$data->producturl = 'https://script.google.com/macros/s/AKfycbx5raOsCH1SfPRlm34dV8vHIpyDYvm9hKh_4bAteYVruIJ7TCSs8j7UWXV6jwX_s7XJ2A/exec';
				$data->s3bucket = 's3://eps.mentorcloudservices.com/embedded';
				$data->dbname = 'cveportal';
				$data->name = 'Mentor Graphics';
				break;
			default:
				return null;
		}
		return $data;
	}
}