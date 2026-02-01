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

	if($argc < 2)throw new Exception("Please supply a remote host name an argument to this script");
	$remoteHostName = $argv[1];
	$open = $argc >= 3 && boolval($argv[2]);
	
	
	//Retreive remote-host data from webservice
	$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://network.bulan-baru.com:8001/api/");
	$log->info("Verifying server has remote-host $remoteHostName from $apiBaseURL...");
	$requestParams = array('remote_host_name' => $remoteHostName);
	$req = APIMakeRequest::createGetRequest($apiBaseURL, 'remote-host', $requestParams);
	$remoteHostData = $req->request();
	if(!$remoteHostData || !isset($remoteHostData['remote_host_name'])){
		throw new Exception("Cannot find $remoteHostName @ $apiBaseURL");
	}
	
	//Now update
	$log->info("Requesting ".($open ? 'open' : 'close')." $remoteHostName from $apiBaseURL...");
	$payload = array();
	$payload['remote_host_name'] = $remoteHostName;
	if($open){
		$payload['request_open'] = 1;
		$payload['comments'] = "Requesting opening $remoteHostName";
	} else {
		$payload['request_open'] = 0;
		$payload['comments'] = "Requesting closing $remoteHostName";
	}
	$req = APIMakeRequest::createPutRequest($apiBaseURL, 'open-remote-host', $payload);
	$req->request();
	$log->info("Updated server");

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
