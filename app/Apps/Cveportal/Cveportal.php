<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Nvd;
use App\Apps\Cveportal\Svm;
use App\Apps\Cveportal\Cve;
use App\Apps\Cveportal\Product;
use Artisan;
class Cveportal extends App{
	public $scriptname = 'cveportal';
	public $timezone='Asia/Karachi';
	public $jira_fields = ['key','status','statuscategory','summary','versions','description']; 
    public $jira_customfields = ['cve_id'=>'customfield_17645','product_id'=>'monitoring_list','publish'=>'Publish','triage'=>'customfield_10444'];
    public $admin = 'mumtaz_ahmad@mentor.com';	
	public $jira_url = 'https://jira.alm.mentorg.com';
	public $default_triage_status = 'Investigate';
    //public $jira_customfields = ['cve_id'=>'customfield_10444','product_id'=>'External ID'];  
	
	public function __construct($options=null)
    {
		parent::__construct($this);

    }
	public function TimeToRun($update_every_xmin=1)
	{
		return parent::TimeToRun($update_every_xmin);
	}
	function IssueParser($code,$issue,$fieldname)
	{
		switch($fieldname)
		{
			case 'publish':
				if(isset($issue->fields->customFields[$code]))
					//return $issue->fields->customFields[$code][0]->value;
					return 1;
				else
					return 0;
				break;
			case 'triage':
			case 'product_id':
			case 'cve_id':
				
				if(isset($issue->fields->customFields[$code]))
					return $issue->fields->customFields[$code];
				return null;
				break;
			default:
				dd('"'.$fieldname.'" not handled in IssueParser');
		}
	}
	
	public function Rebuild()
	{
		//$this->db->cards->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	public function Script()
	{
		$options = $this->Options();
		Artisan::call('product:sync',$options );
		Artisan::call('svm:sync', $options);
		Artisan::call('nvd:sync', $options);
		Artisan::call('cve:sync', $options);
	}
}