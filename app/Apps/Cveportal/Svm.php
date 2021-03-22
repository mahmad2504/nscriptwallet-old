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
	public function Script()
	{
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
			echo "Fetching component list ";
			$components =  $this->getContentBycURL('/common/monitoring_lists/'.$monitoring_list_id.'/components');
			
			echo "\r\n" . count($components)." Found\r\n";
			echo "Fetching list notifications ";
			$notifications = $this->getContentBycURL('/common/monitoring_lists/'.$monitoring_list_id.'/notifications');
			echo "\r\n" . count($notifications)." Found\r\n";
			$count = count($components);
			$i=0;
			$monitoring_list = new \StdClass();
			$monitoring_list->id = $monitoring_list_id;
			$monitoring_list->components = [];
			foreach($components as $componentid)
			{
					$monitoring_list->components[$componentid] = $componentid;
					$query =['id'=>$componentid];
					$projection = ['projection'=>['_id'=>0]];
					$component = $this->db->cpe2->findOne($query,$projection);
					$i++;
					if($component != null)
					{
							echo $i."/".$count." Scanning  ".$component->component_name."[".$component->version."] notifications   ".$component->notifications_count." Found\r\n";
							continue;
					}

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
					echo $i."/".$count." Scanning  ".$component->component_name."[".$component->version."] notifications";
					$component->notifications =  $this->getContentBycURL('/public/components/'.$componentid.'/notifications');
					$component->_notifications = [];
					$component->notifications_count = count($component->notifications);
					echo "    ".count($component->notifications)." Found\r\n";
					foreach($component->notifications as $notification)
					{
							$query =['id'=>$notification->id];
							$projection = ['projection'=>['_id'=>0]];
							$n = $this->db->notifications->findOne($query,$projection);
							if($n == null)
							{
									echo "Fetching Notification [id=".$notification->id."] data"."\r\n";
									$notification->data = $this->getContentBycURL('/public/notifications/'.$notification->id);
									$this->db->notifications->updateOne($query,['$set'=>$notification],['upsert'=>true]);
							}
							else
							{
									if($notification->last_update!=$n->last_update)
									{
											echo "Fetching Notification ".$notification->id." details from svm"."\r\n";
											$notification->data = $this->getContentBycURL('/public/notifications/'.$notification->id);
											$this->db->notifications->updateOne($query,['$set'=>$notification],['upsert'=>true]);
									}
									else
											$notification = $n;
							}
							$component->_notifications[] = $notification;
					}
					$component->notifications = $component->_notifications;
					unset($component->_notifications);

					$component->cve = [];

					foreach($component->notifications as $notification)
					{
						if(isset($notification->data->cve_references))
						{
							foreach($notification->data->cve_references as $cve)
							{
								$cve = 'CVE-'.$cve->year."-".$cve->number;
								$component->cve[$cve] = $cve;
								if(isset($notification->data->solution_status))
									$sol = $notification->data->solution_status.":".$notification->data->solution_details;
								else
									$sol = $notification->data->solution_details;
								$component->solution[$cve][] = $sol;
							}
						}
					}
					$component->cve = array_values($component->cve);
					unset($component->notifications);
					$component->valid = 1;
					$query =['id'=>$componentid];
					$this->db->cpe2->updateOne($query,['$set'=>$component],['upsert'=>true]);
			}
			$query =['id'=>$monitoring_list->id];
			if(count($monitoring_list->components) > 0)
			{
					$components = $monitoring_list->components;
					$monitoring_list->components = array_values($monitoring_list->components);
					$this->db->monitoring_lists->updateOne($query,['$set'=>$monitoring_list],['upsert'=>true]);
					$monitoring_list->components = $components;
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
		$this->db->cpe->drop();
		$cpe2 = $this->db->cpe2->findOne([]);	
		if($cpe2 != null)
		{
			$this->mongo->admin->command(['renameCollection'=>$this->dbname.'.cpe2','to'=>$this->dbname.'.cpe']);
		}
	}
}