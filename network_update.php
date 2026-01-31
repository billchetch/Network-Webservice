<?php
/**
 * Script to be run regularly by a cron job.  Performing various network related funcitons
 * 
 * 1. Opening and closing SSH tunnels to allow connection from a client via a proxy to the machine that is running this <script class=""></script>
 * The concept here is to execute on this machine:
 * 
 * 		ssh -R :<server-port>:localhost:<local-port> <server-user>:<server address> 
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
 * The specific details and whether or not to even open the reverse tunnel is managed via the 'network web service' on the server and it's this script that
 * connects to this webservice to then open the tunnel or close it.
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
$doExec = false; //for debuggging ... set to true to actually execute system commands!
try{
	$lf = "\n";
	$log = Logger::getLog('network update', Logger::LOG_TO_SCREEN);
    $log->start();

	//Remote Host stuff
	try{
		//Retreive remote-host data from webservice
		$remoteHostName = Config::get('REMOTE_HOST_NAME', 'bbrpi-dev01');
		$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://network.bulan-baru.com:8001/api/");
		$log->info("Requesting remote-host info for $remoteHostName from $apiBaseURL...");
		$requestParams = array('remote_host_name' => $remoteHostName);
		$req = APIMakeRequest::createGetRequest($apiBaseURL, 'remote-host', $requestParams);
		$remoteHostData = $req->request();
		$log->info("Successfully obtained data for remote-host $remoteHostName");

		//Generate the ssh reverse tunnel command
		$sshOpen = Config::get('OPEN_SSH_TUNNEL', null);
		if(!$sshOpen){
			throw new Exception("No ssh open command found for this script!");
		}
		$sshClose = Config::get('CLOSE_SSH_TUNNEL', "kill -9 {PID}");
		if(!$sshClose){
			throw new Exception("No ssh close command found for this script!");
		}
		
		$requestOpen = $remoteHostData['request_open'];
		$serverPort = $remoteHostData['server_port'];
		$pid = getPID($sshOpen);

		if($requestOpen){
			$log->info("Remote host request to open reverse tunnel!");
			if($pid > 0){
				$log->info("Process $pid already running so ignoring request to open!");
			} else {
				$sshOpen = str_replace('{SERVER_PORT}', $serverPort, $sshOpen);
				$openAndRunInBackground = $sshOpen.' >/dev/null 2>&1  &';
				$log->info("Open tunnel using: $openAndRunInBackground");
				if($doExec)exec($openAndRunInBackground);
				$pid = 10; //getPID($sshOpen);
				if($pid > 0){
					$log->info("Process $pid started! So updating server @ $apiBaseURL");
					$payload = array();
					$payload['remote_host_name'] = $remoteHostName;
					$payload['request_open'] = $requestOpen;
					$req = APIMakeRequest::createPutRequest($apiBaseURL, 'remote-host', $payload);
					$req->request();
				} else {
					throw new Exception("Process failed to start as no PID can be found using $sshOpen as search string");
				}
			}
		} else {
			$log->info("Remote host set to close reverse tunnel");
			if($pid > 0){
				$log->info("Found process $pid matching $sshOpen");
				$sshClose = str_replace('{PID}', $pid, $sshClose);
				$log->info("Attempting to kill process $pid with $sshClose...");
				if($doExec)exec($sshClose);
				$log->info("Process killed!");
			} else {
				$log->info("No process found searching on $sshOpen so ignoring request to close!");
			}
		}
		

	/*
		//check for ssh tunnels
		if($data && !empty($data['ssh-tunnels'])){
			$log->info("Checking ssh-tunnels...");
			$tunnels = $data['ssh-tunnels'];
			if(isset($tunnels[$tunnelEndpoint])){
				$connect = $tunnels[$tunnelEndpoint];
				$sshOpen = Config::get('OPEN_SSH_TUNNEL', "ssh -tt -i /home/pi/.ssh/bbaws.pem ec2-user@13.59.14.192 -R 2222:localhost:22");
				$sshClose = Config::get('CLOSE_SSH_TUNNEL', "kill -9 {PID}");
				$pid = isProcessRunning($sshOpen);

				$sshClose = str_replace('{PID}', $pid, $sshClose);
				if($connect){					
					$log->info("Remote $apiBaseURL requests to open $tunnelEndpoint ...");
					if($pid > 0){
						$log->info("Process $pid already running so ignoring connect request!");
					} else {
						$openAndRunInBackground = $sshOpen.' >/dev/null 2>&1  &';
						$log->info("Opening tunnel with $openAndRunInBackground");
						exec($openAndRunInBackground); //run in background
						$log->info("Process started!");
					}
				} else {
					$log->info("Remote $apiBaseURL requests to disconnect $tunnelEndpoint...");
					if($pid > 0){
						$log->info("Killing process $pid with $sshClose...");
						exec($sshClose);
						$log->info("Process killed!");
					} else {
						$log->info("Process not running so ignoring disconnect!");
					}
				}
			} else {
				$log->warning("Tunnel host $tunnelEndpoint not found in sys info data");
			}

		} //end ssh tunnels
	} */
	} catch (Exception $e){
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
