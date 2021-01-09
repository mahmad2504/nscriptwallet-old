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
	public function __construct($options=null)
    {
		parent::__construct($this);
    }
}