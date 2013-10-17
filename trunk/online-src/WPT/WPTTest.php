<?php
// turn on all errors
error_reporting(E_ALL);
// turn off all errors
//error_reporting ( 0 );
require_once 'CBUtils/CBAbstract.php';
require_once 'Video/YoutubeUploader.php';
class WPTTest 
//extends CBAbstract 
{

	function constructClass() {
		$this -> test2();
	}

	function test1() {
		$this -> getLogger() -> logInfo("Testing my log info");
		trigger_error('Testing trigger error', E_USER_ERROR);
		throw new Exception('Uncaught Exception');
		echo "Not Executed\n";
	}

	function test2() {
		$api = 'https://gdata.youtube.com/feeds/api/users/';
		$user = "cgotgame";
		$headers = get_headers($api . $user, true);
		if ($headers[0] == "HTTP/1.0 200 OK") {
			echo("Youtube account: ".$user.' Exist!!!');
		}else{
			echo("Youtube account: ".$user.' DOES NOT Exist!!!');
		}
	}
	
}

$test = new WPTTest();
$test->test2();
?>