<?php

class NetworkService extends \chetch\db\DBObject{
	
	public static function initialise(){
		$t = \chetch\Config::get('NETWORK_SERVICES_TABLE', 'net_services');
		self::setConfig('TABLE_NAME', $t);
		self::setConfig('SELECT_SQL', "SELECT * FROM $t");

		self::setConfig('SELECT_ROW_FILTER', "service_name=:service_name");
	}
	
	public static function getServiceyByNetworkParams($domain, $port){
		$filter = "domain=':domain' AND endpoint_port=:endpoint_port";
		$params['domain'] = $domain;
		$params['endpoint_port'] = $port;
		$collection = self::createCollection($params, $filter);
		return count($collection) > 0 ? $collection[0] : null;
	}

	public function __construct($rowdata){
		parent::__construct($rowdata);
	}	
}
?>