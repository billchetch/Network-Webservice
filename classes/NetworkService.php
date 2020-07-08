<?php

class NetworkService extends \chetch\db\DBObject{
	
	public static function initialise(){
		$t = \chetch\Config::get('NETWORK_SERVICES_TABLE', 'net_services');
		self::setConfig('TABLE_NAME', $t);
		self::setConfig('SELECT_SQL', "SELECT * FROM $t");
		
	}
	
	public function __construct($rowdata){
		parent::__construct($rowdata);
		
		
	}
}