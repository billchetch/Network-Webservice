<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\network\Network as Network;
use chetch\Utils as Utils;

try{
	$lf = "\n";
	
	echo "Default gateway: ".Network::getDefaultGatewayIP().$lf;
	//print_r(gethostbynamel(trim(exec("hostname"))));
	print_r($_SERVER);

} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>