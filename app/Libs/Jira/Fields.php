<?php
namespace App\Libs\Jira;

use JiraRestApi\Field\Field;
use JiraRestApi\Field\FieldService;
use JiraRestApi\JiraException;
use JiraRestApi\Configuration\ArrayConfiguration;
use App;
class Fields 
{
	private $fields = [];
	private $default = ['key','status','issuelinks','timetracking'];
	private $native = [];
	private $custom = ['story_points'=>'Story Points','sprint'=>'Sprint'];
	private $conf_filename = null;
	private $app = null;
	public function __construct($app,$auto_initialize=1)
    {
		$this->app=$app;
		if($auto_initialize)
		{
			$obj = $app->Read('jirafields');
			if($obj != null)
				$this->fields = $obj->fields;
		}
		$this->init();
    }
	public function GetApp()
	{
		return $this->app;
	}
	public function Exists()
	{
		if(count($this->fields)>0)
			return true;
		return false;
	}
	private function isAssoc(array $arr)
	{
		if (array() === $arr) return false;
		return array_keys($arr) !== range(0, count($arr) - 1);
	}

	public function Set($fields)
	{
		if($this->isAssoc($fields))
			$this->custom = $fields;
		else
		{
			$this->native = $fields;
			foreach($this->native as $field)
				$this->fields[$field]=$field;
		}
		$this->init();
	}
	private function init()
	{
		foreach($this->fields as $key=>$field)
		{
			if(isset($field['id']))
				$this->$key = $field['id'];
			else
				$this->$key = $key;
		}
	}
	public function __get($field)
	{
		if(isset($this->fields->$field))
		{
			if(is_object($this->fields->$field))
			{
				return $this->fields->$field->id;
			}
			return $this->fields->$field;
		}
		else
			return null;
	}
    public function Dump()
    {
		$this->fields = [];
		try 
		{
			$fieldService = Jira::GetFieldService();
			
			// return custom field only. 
			$ret = $fieldService->getAllFields(Field::CUSTOM);
			foreach($ret as $field)
			{
				foreach($this->custom as $variablename=>$fieldname)
				{
					if(is_object($fieldname))
					{
						//echo $variablename."\n";
						continue;
					}
					if($fieldname == $field->name)
					{
						$this->fields[$variablename] = $field; 
						$this->fields[$variablename]->variablename = $variablename;
					}
				}
            	//dd($field);
			}
			foreach($this->fields as $variablename=>$field)
			{
				if(!is_object($field))
				{
					echo "Field ".$field." not set\n";
					exit();
				}
			}
			foreach($this->native as $field)
				$this->fields[$field]=$field;
			foreach($this->default as $field)
				$this->fields[$field]=$field;
			$obj =  new \StdClass();
			$obj->id = 'jirafields';
			$obj->fields = $this->fields;
			$this->app->Save($obj);
			$obj = $this->app->Read('jirafields');
			if($obj != null)
			    $this->fields = $obj->fields;
				
			$this->init();
		} 
		catch (JiraRestApi\JiraException $e) 
		{
			$this->assertTrue(false, 'testSearch Failed : '.$e->getMessage());
		}
    }
}
