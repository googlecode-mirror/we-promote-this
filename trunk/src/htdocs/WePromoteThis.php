<?php
require_once ("CB/CBUtils/CBAbstract.php");
require_once ("CB/WPTVideoCreator.php");

class WePromoteThis extends CBAbstract {
	
	function constructClass() {
		//Check for updates first
		$updated = $this->checkForUpdates ();
		if ($updated) {
			echo ("Your software has been updated and will need to be restarted.");
		} else {
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
		}
		exit ( 0 );
	}
	
	function checkForUpdates() {
		$updated = false;
		$fileContents = file_get_contents ( ONLINE_LASTEST_VERSION_LOCATION );
		$fileArray = explode ( ',', $fileContents );
		$version = $fileArray [0];
		echo ("Version from online: $version<br>");
		echo ("My Version : " . VERSION_NUMBER . "<br>");
		$compareResults = $this->versionCompare ( VERSION_NUMBER, $version );
		echo ("Compare results: $compareResults<br>");
		if ($compareResults < 0) {
			$this->getNewConfigFiles ();
			$updated = true;
		}
		return $updated;
	}
	
	function getNewConfigFiles() {
		$configFile = dirname ( __FILE__ ) . "/CB/CBUtils/configuration.xml";
		$newFileContents = file_get_contents ( ONLINE_CONFIG_FILE_LOCATION );
		file_put_contents ( $configFile, $newFileContents );
	}
	
	function versionCompare($mine, $theirs) {
		// break into arrays, start comparing from first entry
		$mineArray = explode ( '.', $mine );
		$theirArray = explode ( '.', $theirs );
		// Make both arrays same length
		while ( count ( $theirArray ) < count ( $mineArray ) ) {
			$theirArray [] = 0;
		}
		while ( count ( $mineArray ) < count ( $theirArray ) ) {
			$mineArray [] = 0;
		}
		
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