<?php
namespace App\Apps\Charter;
use App\Apps\App;
use App\Apps\Income;
use Carbon\Carbon;
use App\Email;

class Charter{
	public $timezone='Asia/Karachi';
	public $scriptname = 'Finance';
	public $options = 0;
	public function __construct($options=null)
    {
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
    }
	public Function AddAccount($type,$name)
	{
		switch(strtolower($type))
		{
			case 'income':
				var income = new Income($name);
	}
}