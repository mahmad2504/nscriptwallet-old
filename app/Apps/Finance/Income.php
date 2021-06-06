<?php
namespace App\Apps\Income;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;

class Income{
	public function __construct($name)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		parent::__construct($this);
		
    }
	
}