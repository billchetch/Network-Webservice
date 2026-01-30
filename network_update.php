<?php
/**
 * Script to be run regularly by a cron job.  Performing various network related funcitons
 * 
 * 1. Opening and closing SSH tunnels to allow connection from a client via a proxy to the machine that is running this <script class=""></script>
 * The concept here
 */

require_once('_include.php');

use chetch\Config as Config;
use chetch\sys\Logger as Logger;
use chetch\sys\SysInfo as SysInfo;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

function isProcessRunning($searchFor){
	$searchFor = str_replace('~/', getenv('HOME').'/', $searchFor);
	$check = "ps aux | grep '[s]sh'";
	$lines = array();
	exec($check, $lines);
	$pid = 0;
	if(!empty($lines) && count($lines) > 0){
		foreach($lines as $line){
			if(strpos($line, $searchFor) !== false){
				$line = $ro = preg_replace('/\s+/', ' ', $line);
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
	$local = false;	
	$tunnelEndpoint = Config::get('SSH_TUNNEL_ENDPOINT', 'bills-macbook');
	$openTunnel = true;

	$log = Logger::getLog('network update', Logger::LOG_TO_SCREEN);
    $log->start();

	if($local){
		$si = SysInfo::createInstance();
		$log->info("Getting network-data from local SysInfo");
		$data = $si->getData('network-data');
		$log->info("Seting endpoint $tunnelEndpoint to $openTunnel");
		$data['ssh-tunnels'][$tunnelEndpoint] = $openTunnel;
		$si->setData('network-data', $data);
		$log->info("Successfully updated!");
	} else {
		$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://newtork.bulan-baru.com:8001/api/");
		$log->info("Requesting network-data from remote SysInfo @ $apiBaseURL...");
		$req = APIMakeRequest::createGetRequest($apiBaseURL, 'network-data');
		$data = $req->request();
		$log->info("Successfully obtained network-data");

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
						$log->info("Opening tunnel with $sshOpen");
						exec($sshOpen.' >/dev/null 2>&1  &'); //run in background
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
