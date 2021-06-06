<?php
namespace App\Apps\Cveportal;
use App\Apps\App;
use App\Libs\Jira\Fields;
use App\Libs\Jira\Jira;
use Carbon\Carbon;
use App\Email;
use App\Apps\Cveportal\Product;

class Svm extends Cveportal{
	public $svmurl='https://svm.cert.siemens.com/portal/api/v1';
	public $svmproxyserver='http://cyp-fsrprx.net.plm.eds.com:2020';
	public $options = 0;
	public $scriptname = "cveportal:svm";
	public function __construct($options=null)
    {
		$product = new Product();
		$this->monitoring_list_ids = $product->MonitoringLists();
		if($this->monitoring_list_ids  == null)
		{
			dump("Monitoring lists not found");
		}
		foreach($this->monitoring_list_ids as $monitoring_list_id)
		{
			$p = $product->DbGet($monitoring_list_id);
			if($p == null)
				continue;
			if(count($p->parents)>0)
				$this->sublists[$monitoring_list_id]=$p->parents->jsonSerialize();
		}
		
		$this->namespace = __NAMESPACE__;
		$this->mongo_server = env("MONGO_DB_SERVER", "mongodb://127.0.0.1");
		$this->options = $options;
		parent::__construct($this);
		
		

    }
	public function TimeToRun($update_every_xmin=10)
	{
		return true;
		return parent::TimeToRun($update_every_xmin);
	}
	public function Rebuild()
	{
		$this->db->monitoring_lists->drop();
		$this->options['email']=0;// no emails when rebuild
	}
	function getContentBycURL($strURL)
	{
		//echo $strURL."\n";
		$strURL = $this->svmurl.$strURL;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // Return data inplace of echoing on screen
		curl_setopt($ch, CURLOPT_URL, $strURL);
		curl_setopt($ch, CURLOPT_VERBOSE, '0');
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, '2');
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, '1');
		curl_setopt($ch, CURLOPT_SSLCERT, getcwd() . "/Z003UJ3F_cert.pem");
		curl_setopt($ch, CURLOPT_SSLKEY, getcwd() . "/Z003UJ3F_key.pem");
		//New commands
		//curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		curl_setopt($ch, CURLOPT_PROXY, $this->svmproxyserver );
		curl_setopt($ch, CURLOPT_PROXYPORT, '2020');
		curl_setopt($ch, CURLOPT_PROXYUSERPWD, env("SVM_USER").':'.env("SVM_PASS"));
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
		curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
		//curl_setopt($ch, CURLOPT_KEEP_SENDING_ON_ERROR, TRUE);

		curl_setopt($ch, CURLOPT_CAINFO, getcwd() . "/siemens_root_ca_v3.0_2016.pem");
		curl_setopt($ch, CURLOPT_CAPATH, getcwd() . "/siemens_root_ca_v3.0_2016.pem");
		//curl_setopt($ch, CURLOPT_CAINFO, "/etc/ssl/certs/ca-certificates.crt");
		$rsData = curl_exec($ch);
		$error = curl_error($ch);
		if($error != null)
		{
				echo $error;
				return [];
		}
		$data = json_decode($rsData);

		if(isset($data->errors))
		{
				json_encode($data->errors);
				return [];
		}
		curl_close($ch);
		return $data;
	}
	public function GetCpe($components/*array*/)
	{
		return $this->db->cpe->find(['id'=>['$in' => $components]]);
	}
	public function GetComponents($monitoring_list_id)
	{
		echo "Fetching component list ";
		$components =  $this->getContentBycURL('/common/monitoring_lists/'.$monitoring_list_id.'/components');
		echo "\r\n" . count($components)." components found\r\n";
		$output = [];
		foreach($components as $componentid)
		{
			$query =['id'=>$componentid];
			$projection = ['projection'=>['_id'=>0]];
			$component = $this->db->components->findOne($query,$projection);
			if($component == null)
			{
				echo "Fetching Component [id=".$componentid."] details from svm"."\r\n";
				$component = $this->getContentBycURL('/public/components/'.$componentid);
				$component->id = $componentid;
				$this->db->components->updateOne($query,['$set'=>$component],['upsert'=>true]);
			}
			$output[$componentid]=$component;
		}
		return $output;
	}
	public function GetNotifications($monitoring_list_id)
	{
		echo "Fetching list notifications ";
		$notifications = $this->getContentBycURL('/common/monitoring_lists/'.$monitoring_list_id.'/notifications');
		echo "\r\n" . count($notifications)." notifications found\r\n";	
		$output = [];
		foreach($notifications as $notification)
		{
			$query =['id'=>$notification->id];
			$projection = ['projection'=>['_id'=>0]];
			$n = $this->db->notifications->findOne($query,$projection);
			//$n = null;
			if($n == null)
			{
				dump("Fetching new notification ".$notification->id);
				//$notification->id = 2694;
				$notification->data = $this->getContentBycURL('/public/notifications/'.$notification->id);
				$this->db->notifications->updateOne($query,['$set'=>$notification],['upsert'=>true]);
				$output[] = $notification;
				//dd($notification);
				continue;
			}
			
			if(isset($notification->last_update))
			{
				/*if($notification->id == 68891)
				{
					dump($n);
					dd($notification);
				}*/
				if($notification->last_update != $n->last_update)
				{
					dump("Fetching notification updates ".$notification->id);
					$notification->data = $this->getContentBycURL('/public/notifications/'.$notification->id);
					
					$this->db->notifications->updateOne($query,['$set'=>$notification],['upsert'=>true]);
				}
			}
			else
			{
				if($notification->publish_date != $n->publish_date)
				{
					dump("Fetching notification updated ".$notification->id);
					$notification->data = $this->getContentBycURL('/public/notifications/'.$notification->id);
					$this->db->notifications->updateOne($query,['$set'=>$notification],['upsert'=>true]);

				}
			}
			$output[] = $n;
		}
		return $output;
	}
	function GetComponentsWithCVE($notifications,$components)
	{
		$output = [];
		foreach($notifications as $notification)
		{
			foreach($notification->data->assigned_components as $acid)
			{
				if(isset($components[$acid]))
				{
					$components[$acid]->notifications[$notification->id]=$notification;
					$output[$acid]=$components[$acid];
				}
			}
		}
		return $output;
	}
	function ParseNotifications($component)
	{
		$component->cve = [];
	    //if(count($component->notifications) > 0)
		//	dd($component->notifications);
		foreach($component->notifications as $notification)
		{
			$base_score = 0;
			$vector = '';
			$cvss_version=0;
			if(isset($notification->data->cvss_v3_metrics->base_score))
			{
				$base_score = $notification->data->cvss_v3_metrics->base_score;
				$vector = $notification->data->cvss_v3_metrics->vector;
				$cvss_version=3;
			}
			else 
			{
				if(isset($notification->data->cvss_v2_metrics->base_score))
				{
					$base_score = $notification->data->cvss_v2_metrics->base_score; 
					$vector = $notification->data->cvss_v2_metrics->vector;
					$cvss_version=2;
				}
			}
			
			if(isset($notification->data->cve_references))
			{
				foreach($notification->data->cve_references as $cve)
				{
					if($cve->number < 100)
					{
						$cve->number="00".$cve->number;
						
					}
					else if($cve->number < 1000)
					{
						$cve->number="0".$cve->number;
						
					}
					$cve = 'CVE-'.$cve->year."-".$cve->number;
					$component->cve[$cve] = $cve;
					//if(13829 == $notification->id)
					//	dd($notification);
					if(isset($notification->data->solution_status))
						$sol = $notification->data->solution_status;//.":".$notification->data->solution_details;
					else
						$sol = $notification->data->solution_details;
					
					$component->title[$cve][$notification->id] = $notification->data->title;
					$component->notification_ids[$cve][$notification->id]=$notification->id;
					
					$component->publish_date[$cve][$notification->id] = explode("T",$notification->data->publish_date)[0];
					if($notification->data->last_update==null)
						$component->last_update[$cve][$notification->id] = $component->publish_date[$cve][$notification->id];
					else
						$component->last_update[$cve][$notification->id] = explode("T",$notification->data->last_update)[0];
					
					$component->solution[$cve][$notification->id] = $sol;
					
					$component->basescore[$cve][$notification->id] = $base_score;
					$component->vector[$cve][$notification->id] = $vector;
					$component->cvss_version[$cve][$notification->id] = $cvss_version;
					$component->priority[$cve][$notification->id] = $notification->data->priority;
				}
			}
		}
		$component->cve = array_values($component->cve);
		foreach($component->cve as $cve)
		{
			$component->notification_ids[$cve] = array_values($component->notification_ids[$cve]);
		}
		//if(count($component->notification_ids[$cve])>1)
		//	dd($component);
		return $component;
	}
	public function Script()
	{
		$updated=0;
		$this->db->cpe2->drop();
		$monitoring_lists = [];
		if($this->monitoring_list_ids  == null)
		{
			dump("Monitoring lists not found");
			return;
		}
		
		foreach($this->monitoring_list_ids as $monitoring_list_id)
		{
			echo "Processing monitoring list [id=".$monitoring_list_id."]\r\n";
			$components = $this->GetComponents($monitoring_list_id);
			if(count($components)==0)
			{
				dump($monitoring_list_id." has zero components");
			}
			$notifications =  $this->GetNotifications($monitoring_list_id);
			$components = $this->GetComponentsWithCVE($notifications,$components);
			echo count($components)." components found with CVEs\r\n";
			$count = count($components);
			$i=0;
			$monitoring_list = new \StdClass();
			$monitoring_list->id = $monitoring_list_id;
			$monitoring_list->components = [];
			foreach($components as $componentid=>$component)
			{
				$monitoring_list->components[$componentid] = $componentid;
				$i++;
				echo $i."/".$count." Scanning  ".$component->component_name."[".$component->version."] notifications";
				echo "    ".count($component->notifications)." Found\r\n";
				$this->ParseNotifications($component);
				unset($component->notifications);
				$component->valid = 1;
				$query =['id'=>$componentid];
				$this->db->cpe2->updateOne($query,['$set'=>$component],['upsert'=>true]);
				$updated=1;
			}
			$query =['id'=>$monitoring_list->id];
			if(count($monitoring_list->components) > 0)
			{
				$components = $monitoring_list->components;
				$monitoring_list->components = array_values($monitoring_list->components);
				$this->db->monitoring_lists->updateOne($query,['$set'=>$monitoring_list],['upsert'=>true]);
				$monitoring_list->components = $components;
				$updated=1;
			}
			$monitoring_lists[$monitoring_list->id] = $monitoring_list;
		}
		if(isset($this->sublists))
		foreach($this->sublists as $id=>$sublist)
		{
			if(!isset($monitoring_lists[$id]))
			{
					$monitoring_list = new \StdClass();
					$monitoring_list->id = $id;
					$monitoring_list->components = [];
					$monitoring_lists[$id] = $monitoring_list;
			}
			$monitoring_list = $monitoring_lists[$id];
			foreach($sublist as $sublist_id)
			{
				if(isset($monitoring_lists[$sublist_id]))
				{
						$sublist = $monitoring_lists[$sublist_id];
						foreach($sublist->components as $comp_id)
						{
								$monitoring_list->components[$comp_id] = $comp_id;
						}
				}
				else
				{
					dump("ERROR :: monitoring list  ".$sublist_id." is not included in monitorin list");
				}
			}
			if(count($monitoring_list->components) > 0)
			{
				$components = $monitoring_list->components;
				$monitoring_list->components = array_values($monitoring_list->components);
				$query =['id'=>$id];
				$this->db->monitoring_lists->updateOne($query,['$set'=>$monitoring_list],['upsert'=>true]);
				$monitoring_list->components = $components;
			}
			//$monitoring_lists[$id]->
		}
		if($updated == 1)
		{
			$this->db->cpe->drop();
			$cpe2 = $this->db->cpe2->findOne([]);	
			if($cpe2 != null)
			{
				$this->mongo->admin->command(['renameCollection'=>$this->dbname.'.cpe2','to'=>$this->dbname.'.cpe']);
			}
		}
	}
}