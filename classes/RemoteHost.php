<?php

class RemoteHost extends \chetch\db\DBObject{
	
	public static function initialise(){
		$t = \chetch\Config::get('REMOTE_HOSTS_SERVICES_TABLE', 'net_remote_hosts');
		self::setConfig('TABLE_NAME', $t);
		self::setConfig('SELECT_SQL', "SELECT * FROM $t");

		self::setConfig('SELECT_ROW_FILTER', "remote_host_name=:remote_host_name");
	}
	
	public static function getByHostName($hostname){
		$params = array();
		$params['remote_host_name'] = $hostname;
		return self::createInstance($params);
	}

	public function __construct($rowdata){
		parent::__construct($rowdata);
	}	
}
?>