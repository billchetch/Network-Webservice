<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\sys\Logger as Logger;
use chetch\sys\SysInfo as SysInfo;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

function isProcessRunning($searchFor){
	$check = 'ps aux | grep -v grep | grep "'.$searchFor.'"';
	$lines = array();
	exec($check, $lines);
	if(!empty($lines) && count($lines) > 0){
		$line = $ro = preg_replace('/\s+/', ' ', $lines[0]);
		$parts = explode(' ', $line);
		if(count($parts) < 2 || !is_numeric($parts[1])){
			throw new Exception("Cannot find PID in $line "); 
		}
		return $parts[1];
	} else {
		return 0;
	}
	return !(empty($lines) || count($lines) == 0);
}

$log = null;
try{
	$lf = "\n";
	$local = false;	
	$openBBRPITunnel = true;

	$log = Logger::getLog('network update', Logger::LOG_TO_SCREEN);
        $log->start();

	if($local){
		$si = SysInfo::createInstance();

		$tunnels = array('bbrpi'=>$openBBRPITunnel);
		$data = array('ssh-tunnels'=>$tunnels);
		$si->setData('network-data', $data);
	} else {
		$apiBaseURL = Config::get('REMOTE_API_BASE_URL', "http://sf.bulan-baru.com:8001/api/");
		$req = APIMakeRequest::createGetRequest($apiBaseURL, 'network-data');
		$data = $req->request();

		//check for ssh tunnels
		if($data && !empty($data['ssh-tunnels'])){
			$log->info("Checking ssh-tunnels...");
			$tunnels = $data['ssh-tunnels'];
			if(isset($tunnels['bbrpi'])){
				$connect = $tunnels['bbrpi'];
				$log->info("BBRPI ssh tunnel connect: $connect");
				$sshOpen = Config::get('OPEN_SSH_TUNNEL_BBRPI', "ssh -tt -i /home/pi/.ssh/bbaws.pem ec2-user@13.59.14.192 -R 2222:localhost:22");
				$sshClose = Config::get('CLOSE_SSH_TUNNEL_BBRPI', "kill -9 {PID}");
				$pid = isProcessRunning($sshOpen);
				$sshClose = str_replace('{PID}', $pid, $sshClose);
				if($connect){					
					if($pid > 0){
						$log->info("Process $pid already running so ignoring connect...");
					} else {
						$log->info("Starting process with $sshOpen");
						exec($sshOpen.' >/dev/null 2>&1  &'); //run in background
					}
				} else {
					if($pid > 0){
						$log->info("Killing process with  $sshClose");
						exec($sshClose);
					} else {
						$log->info("Process not running so ignoring disconnect...");
					}
				}
			} //end BBRPI tunnel

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
