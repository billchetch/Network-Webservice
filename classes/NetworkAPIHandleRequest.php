<?php

use chetch\api\APIException as APIException;
use chetch\network\Network as Network;
use chetch\sys\SysInfo as SysInfo;

class NetworkAPIHandleRequest extends chetch\api\APIHandleRequest{
	

	function getResourceDirectoryListing($type, $folder){
		$path = getcwd().'/resources/'.$folder;
		$files = array_diff(scandir($path), array('.', '..'));
		$html = '';
		foreach($files as $f){
			$resource = pathinfo($f, PATHINFO_FILENAME);
			$href = "../resource/$type/$folder/".$resource.'?time='.time();
			$filesize = chetch\Utils::humanFilesize(filesize($path.'/'.$f));
			$html.= '<a href="'.$href.'">'."$resource ($filesize)</a><br/>";
		}
		return $html; 
	}


	protected function processGetRequest($request, $params){
		
		$data = array();
		$requestParts = explode('/', $request);
		switch($requestParts[0]){
			case 'test':
				$data = array('response'=>"Network test Yeah baby");
				$payload['service_name'] = 'oblong3';
			      $payload['domain'] = "192.168.2.101";
				$payload['endpoint_port'] = 8088;
				$service = NetworkService::createInstance($payload);
				var_dump($service);
				break;
				
			case 'status':
				$data['lan_ip'] = Network::getLANIP();
				$data['wan_ip'] = Network::getWANIP();
				$data['internet'] = Network::hasInternet();
				$data['default_gateway'] = Network::getDefaultGatewayIP();
				break;

			case 'status-lan':
				$data['lan_ip'] = Network::getLANIP();
				break;

			case 'services':
				$services = NetworkService::createCollection(null, null, 'service_name');
				$services = NetworkService::collection2rows($services);
				$lanIP = Network::getLANIP();
				foreach($services as $service){
					$service['lan_ip'] = $lanIP;
					$data[$service['service_name']] = $service;
				}
				break;

			case 'service':
				if(!isset($params['service_name']))throw new Exception("No service name passed in query");
				
				$service = NetworkService::createInstance($params);
				$data = $service->getRowData();
				break;
				
			case 'router-status':
				break;

			case 'remote-hosts':
				$hosts = RemoteHost::createCollection();
				$hosts = RemoteHost::collection2rows($hosts);
				$data = $hosts;
				break;

			case 'remote-host':
				if(!isset($params['remote_host_name']))throw new Exception("No remote host name set in request");
				$host = RemoteHost::createInstance($params);
				//$host = RemoteHost::getByHostName($params['remote_host_name']);
				$data = $host->getRowData();
				if(!isset($data['id']))throw new Exception("Entry for remote host".$params['remote_host_name']." not found!");
				break;

			case 'tokens':
				if(!isset($params['service_id']))throw new Exception("No service id passed in query");
				
				$tokens = NetworkServiceToken::createCollection($params);
				$data = NetworkServiceToken::collection2rows($tokens);
				break;

			case 'token':
				if(!isset($params['service_id']))throw new Exception("No service id passed in query");
				if(!isset($params['client_name']))throw new Exception("No client name passed in query");
				
				$token = NetworkServiceToken::createInstance($params);
				$data = $token->getRowData();
				break;

			case 'resources':
				if(count($requestParts) == 2){
					$dir = $requestParts[1];
					switch(strtolower($dir)){
						case 'apks':
							echo $this->getResourceDirectoryListing('apk', $dir);
							die;

						case 'pdfs':
							echo $this->getResourceDirectoryListing('pdf', $dir);
							die;

						default:
							throw new Exception("$dir is not a recognised resource directory");
							break;
					}
				}
				break;

			case 'resource':
				$this->processGetResourceRequest($request, $params);
				break;

			default:
				throw new Exception("Unrecognised request $request");
				break;
		}
		return $data;
	}

	protected function processPutRequest($request, $params, $payload){
		
		$data = array();
		$requestParts = explode('/', $request);
		
		switch($requestParts[0]){
			case 'service':
				if(empty($payload))throw new Exception("No payload supplied");
				if(empty($payload['service_name']))throw new Exception("Cannot save service as no name is provided");
				if(empty($payload['endpoint_port']))throw new Exception("Cannot save service as no port is provided");
				if(empty($payload['protocols']))throw new Exception("Cannot save service as no protocol is provided");

				if(empty($payload['domain'])){
					$payload['domain'] = $_SERVER['REMOTE_ADDR'];
				}
				$payload['updated'] = self::now(false);

				$service = NetworkService::createInstance($payload);
				$s = NetworkService::getServiceyByNetworkParams($payload['domain'], $payload['endpoint_port']);
				if($s != null && $s->id != $service->id){
					throw new Exception("Cannot save service ".$payload['service_name']." as the service ".$s->get('service_name')." is already using ".$payload['domain'].":".$payload['endpoint_port']);
				}			
				
				$service->write(true);
				$data = $service->getRowData();
				break;

			case 'token':
				if(empty($payload['service_id']))throw new Exception("Cannot save token as no service ID provided");
				if(empty($payload['client_name']))throw new Exception("Cannot save token as no client name provided");
				
				unset($payload['created']);
				unset($payload['id']);

				$token = NetworkServiceToken::createInstance($payload);
				$token->write(true);
				$data = $token->getRowData();
				break;

			case 'remote-host':
				if(empty($payload['remote_host_name']))throw new Exception("Cannot update remote host as no remote_host_name provided");
				unset($payload['id']);

				if(isset($payload['request_open'])){
					if($payload['request_open']){
						$payload['opened_on'] = self::now(false);
						$payload['closed_on'] = null;
					} else {
						$payload['closed_on'] = self::now(false);
					}
				} 
				$payload['last_updated'] = self::now(false);
				$payload['wan_ip'] = $_SERVER['REMOTE_ADDR'];
				$host = RemoteHost::createInstance($payload);
				$host->write(true);
				$data = $host->getRowData();
				break;

			case 'open-remote-host':
				if(empty($payload['remote_host_name']))throw new Exception("Cannot open or close remote host as no remote_host_name provided");
				if(!isset($payload['request_open']))throw new Exception("Cannot open or close remote host as no request_open found in payload");
				unset($payload['id']);

				//Check current status
				$hostname = $payload['remote_host_name'];
				$request2open = $payload['request_open'];
				$host = RemoteHost::getByHostName($hostname);
				if(!$host->getID()){
					throw new Exception("No remote host found with name $hostname");
				}
				if($host->get('request_open') == $request2open){
					throw new Exception("Request to ".($request2open ? 'open' : 'close')." already PUT");
				}

				$payload['last_updated'] = self::now(false);
				if($payload['request_open']){ //If requesting to open we reset previous date info
					$payload['opened_on'] = null;
					$payload['closed_on'] = null;
				} else {
					$payload['closed_on'] = null; //not strictly necessary as it should be null anyways
				}
				$payload['wan_ip'] = $_SERVER['REMOTE_ADDR'];
				$host = RemoteHost::createInstance($payload);
				$host->write(true);
				$data = $host->getRowData();
				break;

			default:
				throw new Exception("Unrecognised api request $request");
		}

		return $data;
	}
}
?>