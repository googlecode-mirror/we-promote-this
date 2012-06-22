<?php
require_once ("CB/CBUtils/CBAbstract.php");
require_once ("CB/WPTVideoCreator.php");

class WePromoteThis extends CBAbstract {
	
	function constructClass() {
		// put in start time
		

		// Call DB and figure out a video to create
		$pid = trim ( $this->getVideoToCreate () );
		echo ("Creating video for $pid at " . date ( "m-d-y h:i:s A" ) . "<br>");
		$WPTVC = new WPTVideoCreator ( );
		$status = $WPTVC->createVideoFor ( $pid );
		//echo("Finished Creating video for $pid at " . date("m-d-y h:i:s A") . "<br>");
		

		// find end time diff
		// if longer than an hour kill process wepromotethi.exe and tvcc.exe
		

		if (stripos ( $status, 'successful' ) !== false) {
			echo ("Another video will be created in 5 secs.<br>");
			$this->refreshCurrentWindow ();
		} else {
			$this->killP ( "TVCC.exe" );
			$this->killP ( "WePromoteThis.exe" );
		}
		exit ( 0 );
	}
	
	function getVideoToCreate() {
		$url = "http://WePromoteThis.com/WePromoteThis/WPTVideoToCreate.php";
		$ch = curl_init ();
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_VERBOSE, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt ( $ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible;)" );
		curl_setopt ( $ch, CURLOPT_URL, $url );
		$response = curl_exec ( $ch );
		curl_close ( $ch );
		return $response;
	}
	
	function refreshCurrentWindow() {
		$javascript = "<script langauge='JavaScript'>

 setTimeout('refresh()',5000);

 function refresh(){
 window.open('WePromoteThis.php','wepromotethis');
 }

 //window.close();
 </script>";
		
		echo ($javascript);
	}
	
	function killP($processName) {
		$command = 'taskkill /F /T /IM ' . $processName;
		exec ( $command, $output, $result );
		//print_r($output);
		echo ($processName . " is now closing...");
	}
	
	function __destruct() {
		parent::__destruct ();
	}

}

$wpe = new WePromoteThis ( );
?>