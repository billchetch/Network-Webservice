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
	$log = Logger::getLog('get remote connections', Logger::LOG_TO_SCREEN);
    $log->start();

	if($argc < 2)throw new Exception("Please supply a remote host name an argument to this script");
	$remoteHostName = $argv[1];
	
	//Retreive remote-host data from webservice
	$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://network.bulan-baru.com:8001/api/");
	$log->info("Requesting connections for $remoteHostName from $apiBaseURL...");
	$requestParams = array('remote_host_name' => $remoteHostName);
	$req = APIMakeRequest::createGetRequest($apiBaseURL, 'remote-connections', $requestParams);
	try{
		$connections = $req->request(); //this will throw if not found

		foreach($connections as $cnn){
			$connection = $cnn['connection'];
			if($cnn['request_open'] && !empty($cnn['opened_on']) && empty($cnn['closed_on'])){
				$log->info("----- $connection is OPEN, opened on ".$cnn['opened_on']);
			} else if($cnn['request_open'] && empty($cnn['opened_on'])){
				$log->info("----- $connection WAITING TO OPEN, updated on ".$cnn['last_updated']);
			} else if(!empty($cnn['opened_on']) && !empty($cnn['closed_on'])){
				$log->info("----- $connection is CLOSED");
			} else if(!$cnn['request_open'] && empty($cnn['closed_on'])){
				$log->info("----- $connection WAITING TO CLOSE");
			}
		}
	} catch (Exception $e){
		$log->warning("$remoteHostName NOT found on server!");
		throw $e;
	}

	$log->finish();

} catch (Exception $e){
	if($log){
		$log->exception($e->getMessage());
        	$log->info("get remote host exited because of exception: ".$e->getMessage());
	} else {
		echo "EXCEPTION: ".$e->getMessage();
	}
}

?>
