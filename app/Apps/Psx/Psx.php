<?php
namespace App\Apps\Psx;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;


class Psx extends App{
	public $timezone='Asia/Karachi';
	public $users_pro = ["-1001270525449"];
	public $users_basic = ['@psx_announcements_basic'];
	public $moderated = ["-1001418170428"];
	public function __construct($options=null)
    {
		parent::__construct($this);
    }
	public function TimeToRun($update_every_xmin=60)
	{
		$now = Carbon::now($this->timezone);
		if(($now->format('H')<8)&&($now->format('H')<20))
		{
			dump('Its sleeping time please');
			return false;
		}
		return parent::TimeToRun($update_every_xmin);
	}
}