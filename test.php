<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\network\Network as Network;
use chetch\Utils as Utils;

try{
	$lf = "\n";
	
	echo Network::getDefaultGatewayIP();
	
} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>