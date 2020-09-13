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
		}
		return $data;
	}
}
?>