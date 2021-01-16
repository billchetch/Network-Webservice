<?php

use chetch\api\APIException as APIException;
use chetch\network\Network as Network;

class NetworkAPIHandleRequest extends chetch\api\APIHandleRequest{
	
	private function getLANIP(){
		if(isset($_SERVER) && isset($_SERVER['SERVER_ADDR'])){
			return $_SERVER['SERVER_ADDR'];
		} else {
			return Network::getLANIP();
		}
	}

	protected function processGetRequest($request, $params){
		$data = array();
		switch($request){
			case 'test':
				$data = array('response'=>"Network test Yeah baby");
				print_r($_SERVER);
				break;
				
			case 'status':
				$data['lan_ip'] = $this->getLANIP();
				$data['wan_ip'] = Network::getWANIP();
				$data['internet'] = Network::hasInternet();
				$data['default_gateway'] = Network::getDefaultGatewayIP();
				break;

			case 'status-lan':
				$data['lan_ip'] = $this->getLANIP();
				break;

			case 'services':
				$services = NetworkService::createCollection(null, null, 'service_name');
				$services = NetworkService::collection2rows($services);
				$lanIP = $this->getLANIP();
				foreach($services as $service){
					$service['lan_ip'] = $lanIP;
					$data[$service['service_name']] = $service;
				}
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
		}
		return $data;
	}

	protected function processPutRequest($request, $params, $payload){
		
		$data = array();
		$requestParts = explode('/', $request);
		
		switch($requestParts[0]){
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