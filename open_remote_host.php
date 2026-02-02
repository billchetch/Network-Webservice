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

	if($argc < 3)throw new Exception("Please supply a remote host name an argument to this script");
	$remoteHostName = $argv[1];
	$connection = $argv[2];
	$open = $argc >= 3 && boolval($argv[3]);
	
	//Retreive remote-host data from webservice
	$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://network.bulan-baru.com:8001/api/");
	$log->info("Verifying server has $remoteHostName $connection from $apiBaseURL...");
	$requestParams = array('remote_host_name' => $remoteHostName, 'connection'=>$connection);
	$req = APIMakeRequest::createGetRequest($apiBaseURL, 'remote-connection', $requestParams);
	$cnn = null;
	try{
		$cnn = $req->request(); //this will thor
		$log->info("$remoteHostName $connection found on server!");
	} catch (Exception $e){
		$log->warning("$remoteHostName $connection NOT found on server!");
		throw $e;
	}

	//Check if an update is required
	if($open){
		$alreadyOpen = $cnn['request_open'];
		if($alreadyOpen){
			throw new Exception("$connection has already been requestd to open!");
		}
	} else {
		$alreadyClosed = !$cnn['request_open'];
		if($alreadyClosed){
			throw new Exception("$connection has already been requestd to close!");
		}
	}
	
	//Now update
	$log->info("Requesting ".($open ? 'open' : 'close')." $remoteHostName $connection from $apiBaseURL...");
	$payload = array();
	$payload['remote_host_name'] = $remoteHostName;
	$payload['connection'] = $connection;
	if($open){
		$payload['request_open'] = 1;
		$payload['comments'] = "Requesting opening $connection";
	} else {
		$payload['request_open'] = 0;
		$payload['comments'] = "Requesting closing $connection";
	}
	$req = APIMakeRequest::createPutRequest($apiBaseURL, 'open-remote-connection', $payload);
	$req->request();
	$log->info("Updated server");

	$log->finish();

} catch (Exception $e){
	if($log){
		$log->exception($e->getMessage());
        $log->info("open remote host exited because of exception: ".$e->getMessage());
		$log->finish();
	} else {
		echo "EXCEPTION: ".$e->getMessage();
	}
}


?>
