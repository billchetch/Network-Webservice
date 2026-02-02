<?php
/**
 * Script to be run regularly by a cron job.  Performing various network related funcitons
 * 
 * 1. Opening and closing SSH tunnels to allow connection from a client via a proxy to the machine that is running this <script class=""></script>
 * The concept here is to execute on this machine (the 'remote machine'):
 * 
 * 		ssh -R :<server-port>:localhost:<remote-port> <server-user>:<server address> 
 * 
 * which will create a 'reverse' tunnel from the server to this machine that can be accessed as follows from the server:
 * 
 * 		ssh -p <server-port> <username-this-machine>@localhost 
 * 
 * Once this is possible then from another machine you can ssh directly to the server and add the above line to your ssh command and
 * it will then open a terminal directly on to this machine effectively using the server as a bridge. For instance:
 * 
 * 		ssh -t <server-user>:<server address> 'ssh -p <server-port> <username-this-machine>@localhost>'
 * 
 * Note the -t switch to not bring up a terminal when connecting to the server and therefore allowing the terminal to be used directly for the 
 * connection to this machine.
 * 
 * The specific details and whether or not to even open the reverse tunnel is managed via the 'network web service' on the server. This script
 * connects to that webservice to  open a tunnel or close it.
 */
require_once('_include.php');

use chetch\Config as Config;
use chetch\sys\Logger as Logger;
use chetch\sys\SysInfo as SysInfo;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

function getPID($searchFor){
	$searchFor = str_replace('~/', getenv('HOME').'/', $searchFor);
	$check = "ps aux | grep '[s]sh'";
	$lines = array();
	exec($check, $lines);
	$pid = 0;
	if(!empty($lines) && count($lines) > 0){
		foreach($lines as $line){
			if(strpos($line, $searchFor) !== false){
				$line = preg_replace('/\s+/', ' ', $line);
				$parts = explode(' ', $line);
				if(count($parts) < 2 || !is_numeric($parts[1])){
					throw new Exception("Cannot find PID in $line "); 
				}
				
				$pid = $parts[1];
				break;
			}
		}
	} else {
		return 0;
	}
	return $pid;
}

/**
 * IMPORTANT!: Read the intro to this script above !^!
 */
$log = null;
try{
	$lf = "\n";
	$log = Logger::getLog('network update', Logger::LOG_TO_SCREEN);
    $log->start();

	//Remote connection stuff
	try{
		//Retreive remote-host data from webservice
		$doExec = Config::get('REMOTE_CONNECTIONS_DO_EXEC', false); //For testing etc.... on live set this flag to true for example
		if(!$doExec)$log->warning("Note doExec = false!");

		$remoteHostName = Config::get('REMOTE_HOST_NAME', 'bbrpi-dev01');
		$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://network.bulan-baru.com:8001/api/");
		$log->info("Requesting connections list for $remoteHostName from $apiBaseURL...");
		$requestParams = array('remote_host_name' => $remoteHostName);
		$req = APIMakeRequest::createGetRequest($apiBaseURL, 'remote-connections', $requestParams);
		$connections = null;
		try{
			$connections = $req->request();
			$log->info("Successfully obtained connections listing for remote-host $remoteHostName");
		} catch (Exception $e){
			$log->warning("$remoteHostName connections NOT found on server!");
			throw $e;
		}

		//Generate the ssh reverse tunnel command
		$sshOpenTemplate = Config::get('OPEN_SSH_TUNNEL', null);
		if(!$sshOpenTemplate){
			throw new Exception("No ssh open command found for this script!");
		}		
		$sshCloseTemplate = Config::get('CLOSE_SSH_TUNNEL', "kill -9 {PID}");
		if(!$sshCloseTemplate){
			throw new Exception("No ssh close command found for this script!");
		}

		$log->info("Found ".count($connections)." connections for $remoteHostName:");

		//loop through the connections
		foreach($connections as $cnn)
		{
			try{
				//use some connection data
				$requestOpen = $cnn['request_open'];
				$connectionName = $cnn['connection'];
				$serverPort = $cnn['server_port'];
				$remotePort = $cnn['remote_port'];
				$sshOpen = str_replace(array('{SERVER_PORT}','{REMOTE_PORT}'), array($serverPort, $remotePort), $sshOpenTemplate);
				$updateServer = false;
				
				//set payload for server update later
				$payload = array();
				$payload['remote_host_name'] = $remoteHostName;
				$payload['connection'] = $connectionName;
				$payload['lan_ip'] = Network::getLANIP();
				$payload['comments'] = "Running script for connection $connectionName with doExec = ".($doExec ? 'false' : 'true');

				//See if we have a ssh process running
				$pid = getPID($sshOpen);

				//Now process the open/close request
				if($requestOpen){
					$log->info("Remote connection request to open $connectionName reverse tunnel !");
					if($pid > 0){
						$log->info("Process $pid already running so ignoring request to open!");
					} else {
						$openAndRunInBackground = $sshOpen.' >/dev/null 2>&1  &';
						$log->info("Open tunnel using: $openAndRunInBackground");
						if($doExec)exec($openAndRunInBackground);
						$pid = getPID($sshOpen);
						if($pid > 0){
							$log->info("Process $pid started! So updating server @ $apiBaseURL");
							$payload['request_open'] = $requestOpen;
							$payload['comments'] = "Process $pid started for connection $connectionName!";
							$updateServer = true;
						} else {
							throw new Exception("Process failed to start as no PID can be found using $sshOpen as search string");
						}
					}
				} else {
					$log->info("Remote $connectionName connection set to close reverse tunnel");
					if($pid > 0){
						$log->info("Found process $pid matching $sshOpen");
						$sshClose = str_replace('{PID}', $pid, $sshCloseTemplate);
						$log->info("Attempting to kill process $pid with $sshClose...");
						if($doExec)exec($sshClose);
						$pid = getPID($sshOpen);
						if($pid > 0){
							throw new Exception("Process failed to die PID $pid can be found using $sshOpen as search string");
						} else {
							$log->info("Process killed!");
							$payload['request_open'] = $requestOpen;
							$payload['comments'] = "Process $pid for connectoin $connectionName killed!";
							$updateServer = true;
						}
					} else {
						$log->info("No process found searching on $sshOpen so ignoring request to close!");
					}
				}
				
				//Now update server
				if($updateServer){
					$log->info("Updating server with $remoteHostName $connectionName info...");
					$req = APIMakeRequest::createPutRequest($apiBaseURL, 'remote-connection', $payload);
					$req->request();
					$log->info("Updated server");
				} else {
					$log->info("No changes required for $remoteHostName $connectionName");
				}
			} catch (Exception $e){
				if($log){
					$log->exception($e->getMessage());
					$log->info("Remote connection exited because of exception: ".$e->getMessage());
				}
			}
		} //end looping through connections
	} catch (Exception $e){ //Exceptions for Remote Host stuff
		if($log){
			$log->exception($e->getMessage());
        	$log->info("Remote connections update exited because of exception: ".$e->getMessage());
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
