<?php

class RemoteConnection extends \chetch\db\DBObject{
	
	public static function initialise(){
		$t = \chetch\Config::get('REMOTE_CONNECTIONS_TABLE', 'net_remote_connections');
		self::setConfig('TABLE_NAME', $t);
		self::setConfig('SELECT_SQL', "SELECT * FROM $t");

		self::setConfig('SELECT_ROW_FILTER', "(remote_host_name=:remote_host_name AND connection=:connection)");
	}
	
	public static function getConnection($hostname, $connection){
		$params = array();
		$params['remote_host_name'] = $hostname;
		$params['connection'] = $connection;
		return self::createInstance($params);
	}

	public function __construct($rowdata){
		parent::__construct($rowdata);
	}	
}
?>