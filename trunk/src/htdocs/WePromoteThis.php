<?php
require_once ("CB/CBUtils/CBAbstract.php");
require_once ("CB/WPTVideoCreator.php");

class WePromoteThis extends CBAbstract {
	
	function __construct() {
		$this->checkForUpdates ();
		parent::__construct ();
	}
	
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
	
	function checkForUpdates() {
		$updateUrl = "http://we-promote-this.googlecode.com/files/latestver.txt";
		$fileContents = file_get_contents ( $updateUrl );
		$fileArray = explode ( ',', $fileContents );
		$version = $fileArray [0];
		if(){
			
		}
	
	}
	
	function getNewConfigFiles(){
		
	}
	
	function versionCompare($mine, $theirs) {
		// break into arrays, start comparing from first entry
		$mineArray = explode ( '.', $mine );
		$theirArray = explode ( '.', $theirs );
		$mVersion = 0;
		$tVersion = 0;
		if (count ( $mineArray ) > 0) {
			$mVersion = ( int ) array_shift ( $mineArray );
		}
		if (count ( $theirArray ) > 0) {
			$tVersion = ( int ) array_shift ( $theirArray );
		}
		if ($mVersion == $tVersion) {
			if (count ( $theirArray ) > 0 || count ( $mineArray ) > 0) {
				return $this->versionCompare ( implode ( '.', $mineArray ), implode ( '.', $theirArray ) );
			} else {
				return 0;
			}
		} else if ($mVersion > $tVersion) {
			return 1;
		} else {
			return - 1;
		}
	
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