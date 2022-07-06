<?php

use chetch\api\APIException as APIException;
use chetch\network\Network as Network;

class NetworkAPIHandleRequest extends chetch\api\APIHandleRequest{
	
	protected function addResourePaths(&$resourcePaths, $resourcePathBase, $resourceType, $resourceDirectory, $resourceID){
		switch($resourceDirectory){
			case 'apks':
				array_push($resourcePaths, $resourcePathBase.'media-controller-debug');
				array_push($resourcePaths, $resourcePathBase.'captains-log-debug');
				break;
		}
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
				if(count($requestParts) == 2 && $requestParts[1] == 'apks'){
					$path = getcwd().'/resources/apks';
					$files = array_diff(scandir($path), array('.', '..'));
					foreach($files as $f){
						$href = '../resource/apk/apks/'.$f;
						echo '<a href="'.$href.'">'.$f.'</a><br/>';
					}
					die;
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

			default:
				throw new Exception("Unrecognised api request $request");
		}

		return $data;
	}
}
?>