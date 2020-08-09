<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\network\Network as Network;
use chetch\Utils as Utils;

try{
	$lf = "\n";
	
	$services = NetworkService::createCollection(null, null, 'service_name');
	$services = NetworkService::collection2rows($services);
	print_r($services);
	
} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>