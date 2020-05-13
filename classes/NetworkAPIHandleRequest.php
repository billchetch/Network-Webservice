<?php

use chetch\api\APIException as APIException;
use chetch\network\Network as Network;

class NetworkAPIHandleRequest extends chetch\api\APIHandleRequest{
	
	protected function processGetRequest($request, $params){
		$data = array();
		switch($request){
			case 'test':
				$data = array('response'=>"Network test Yeah baby");
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
				foreach($services as $service)$data[$service['service_name']] = $service;
				break;
				
			case 'router-status':
				break;
		}
		return $data;
	}
}