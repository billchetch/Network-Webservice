<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

try{
	$lf = "\n";
	
	$payload = array();
	$payload["service_id"] = 2;
	//$payload["token"] = "AAAZZZa";
	$payload["client_name"] = "BBAlarms2";
 
	$inst = NetworkServiceToken::createInstance($payload);
	$data = $inst->getRowData();

	//$req = APIMakeRequest::createDeleteRequest("http://127.0.0.1:8005/api", "entry/1");
	//$req = APIMakeRequest::createGetRequest("http://127.0.0.1:8005/api", "entries");
	//$req = APIMakeRequest::createPutRequest("http://127.0.0.1:8001/api", "token", $payload);
	//$data = $req->request();
	//print_r($req);
	print_r($data);
	
	echo "DONE";

} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>