<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

try{
	$lf = "\n";
	
	$payload = array();
	//$payload["token"] = "AAAZZZa";
	$payload["service_name"] = "fingerfudge";
	$payload["endpoint_port"] = 10234;
	$payload["protocols"] = "tcp";
 
	$inst = NetworkService::createInstance($payload);
	$data = $inst->getRowData();
	print_r($data);

	//$req = APIMakeRequest::createDeleteRequest("http://127.0.0.1:8005/api", "entry/1");
	//$req = APIMakeRequest::createGetRequest("http://127.0.0.1:8005/api", "entries");
	$req = APIMakeRequest::createPutRequest("http://127.0.0.1:8001/api", "service", $payload);
	$data = $req->request();
	//print_r($req);
	print_r($data);
	
	echo "DONE";

} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>