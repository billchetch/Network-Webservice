<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

try{
	$lf = "\n";
	
	$s = "{\"service_name\": \"crayfish9\",\"endpoint_port\":8091,\"protocols\":\"tcp\"}";
	$payload = json_decode($s, true); //array();
	//$payload["token"] = "AAAZZZa";
	/*$payload["service_name"] = "oblong4";
	$payload['domain'] = "192.168.2.102";
	$payload["endpoint_port"] = 8089;
	$payload["protocols"] = "tcp";*/
	$inst = NetworkService::createInstance($payload);
	var_dump($inst); 
	die;
	
	//$req = APIMakeRequest::createDeleteRequest("http://127.0.0.1:8005/api", "entry/1");
	//$req = APIMakeRequest::createGetRequest("http://127.0.0.1:8005/api", "entries");
	$req = APIMakeRequest::createPutRequest("http://127.0.0.1:8001/api", "service", $payload);
	$data = $req->request();
	print_r($data);
	
	echo "DONE";

} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>