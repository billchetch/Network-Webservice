<?php
require_once('_include.php');

use chetch\Config as Config;
use chetch\sys\SysInfo as SysInfo;
use chetch\network\Network as Network;
use chetch\Utils as Utils;
use chetch\api\APIMakeRequest as APIMakeRequest;

try{
	$lf = "\n";
	$local = false;	
	$openBBRPITunnel = true;

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
			$tunnels = $data['ssh-tunnels'];
			if(!empty($tunnels['bbrpi'])){
				$connect = $tunnels['bbrpi'];
				if($connect){
					echo "Connect to bbrpi";
					$lines = array();
					exec('netstat -n', $lines);
					foreach($lines as $line){
						echo $line.$lf;
					}
				} else {
					echo "Disconnect from bbrpi";
				}
			}

		} //end ssh tunnels
	}
	echo "DONE";

} catch (Exception $e){
	echo "EXCEPTION: ".$e->getMessage();
}


?>