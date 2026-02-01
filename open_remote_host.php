<?php
/**
 * This script calls puts to a webservice to request open or close a ssh tunnel to a remote host.
 * See network_update.php for explanation on how remote hosts work.
 */

require_once('_include.php');

use chetch\Config as Config;
use chetch\sys\Logger as Logger;
use chetch\sys\SysInfo as SysInfo;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

$log = null;
try{
	$lf = "\n";
	$log = Logger::getLog('open remote host', Logger::LOG_TO_SCREEN);
    $log->start();

	$open = $argc >= 2 && boolval($argv[1]);
	//Remote Host stuff
	try{
		//Retreive remote-host data from webservice
		$remoteHostName = Config::get('REMOTE_HOST_NAME', 'bbrpi-dev01');
		$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://network.bulan-baru.com:8001/api/");
		$log->info("Requesting ".($open ? 'open' : 'close')." $remoteHostName from $apiBaseURL...");
		
		$payload = array();
		$payload['request_open'] = $open ? 1 : 0;
		$req = APIMakeRequest::createPutRequest($apiBaseURL, 'open-remote-host', $payload);
		$req->request();
		$log->info("Updated server");
	} catch (Exception $e){ //Exceptions for Remote Host stuff
		if($log){
			$log->exception($e->getMessage());
        	$log->info("Remote host update exited because of exception: ".$e->getMessage());
		}
	}

	$log->finish();

} catch (Exception $e){
	if($log){
		$log->exception($e->getMessage());
        	$log->info("Network update exited because of exception: ".$e->getMessage());
	} else {
		echo "EXCEPTION: ".$e->getMessage();
	}
}


?>
