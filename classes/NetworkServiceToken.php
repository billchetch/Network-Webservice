<?php

class NetworkServiceToken extends \chetch\db\DBObject{
	
	public static function initialise(){
		$t = \chetch\Config::get('NETWORK_SERVICE_TOKENS_TABLE', 'net_service_tokens');
		self::setConfig('TABLE_NAME', $t);
		
		$tzo = self::tzoffset();
		$sql = "SELECT *, CONCAT(created,' ', '$tzo') AS created FROM $t";
		self::setConfig('SELECT_SQL', $sql);
		
		self::setConfig('SELECT_DEFAULT_FILTER', "service_id=:service_id");
		//self::setConfig('SELECT_DEFAULT_SORT', "id DESC");
		self::setConfig('SELECT_ROW_FILTER', "service_id=:service_id AND client_name=':client_name'");
	}
	
	public function __construct($rowdata){
		parent::__construct($rowdata);
	}

	public function write($readAgain = false){
		$this->remove('created');
		if(!empty($this->id)){
			$this->set("updated", self::now(false));
		}

		return parent::write($readAgain);
	}
}
?>