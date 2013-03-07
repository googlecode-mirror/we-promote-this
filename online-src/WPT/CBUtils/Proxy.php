<?php
set_time_limit ( 2592000 );

require_once 'CBAbstract.php';

class Proxy extends CBAbstract {
	
	function constructClass() {
	}
	
	function __construct() {
		parent::__construct ();
	}
	function __destruct() {
		parent::__destruct ();
	}
	
	function testProxy($proxy) {
		$testResult = false;
		// This is the page that that we're going to request going through the proxy
		$testpage = "http://www.google.com";
		//$testpage = "http://wfuchs.de/azenv.php";
		// Here we loop through each cell of the array with the proxies in them testing each one until we get to the end of the array
		$fullProxy = $proxy ['proxy'] . ":" . $proxy ['port'];
		//echo ("Testing Proxy: $fullProxy<br>");
		// This script utilizes cURL which is library you can read more about
		//using curl in my intro tutorials
		// starting curl and setting the page to get
		$ch = curl_init ( $testpage );
		$options = array (CURLOPT_RETURNTRANSFER => true, // return web page
CURLOPT_HEADER => false, // don't return headers
CURLOPT_FOLLOWLOCATION => true, // follow redirects
CURLOPT_ENCODING => "", // handle all encodings
CURLOPT_AUTOREFERER => true, // set referer on redirect
CURLOPT_CONNECTTIMEOUT => 10, // timeout on connect
CURLOPT_TIMEOUT => 10, // timeout on response
CURLOPT_PROXY => $fullProxy, //sets the proxy to go through
CURLOPT_HTTPPROXYTUNNEL => TRUE, // sets to use a tunnel proxy as http tunnel 
CURLOPT_PROXYTYPE => CURLPROXY_HTTP ); // Set proxy type
		curl_setopt_array ( $ch, $options );
		
		// makes the curl call do it's work based on what we've set previously and
		//returns that fetched page to $page
		$page = curl_exec ( $ch );
		// cleans up the curl set
		curl_close ( $ch );
		// this will check that there was some html returned, now some sites might block some
		//proxies so you'd want to set for that specific site in the $testpage var and then
		//find something on that page to look for with the below function.
		// if there was a match in the stripos (string postion) function echo that the
		//proxy got the data and works
		if (stripos ( $page, '</html>' ) !== false) {
			//echo ("Proxy Passed: $fullProxy<br>");
			$testResult = true;
		
		// or else echo it doesn't work
		} else {
			//echo ("Proxy Failed: $fullProxy<br>");
			$testResult = false;
		}
		return $testResult;
	}
	
	function deleteProxy($proxy) {
		//$fullProxy = $proxy ["proxy"] . ":" . $proxy ["port"];
		$query = "Select errorCount from proxies where proxy='" . $proxy ['proxy'] . "'";
		$results = $this->getDBConnection()->queryDB ( $query );
		$row = $results-> fetch_assoc();
		$count = $row ['errorCount'];
		$count ++;
		if ($count >= 3) {
			$query = "Delete from proxies where proxy='" . $proxy ['proxy'] . "'";
		
		//echo ("Delete Proxy: $fullProxy<br>");
		} else {
			//echo ("Update Proxy Error ($count): $fullProxy<br>");
			$query = "Update proxies set errorCount=$count where proxy='" . $proxy ['proxy'] . "'";
		}
		$this->getDBConnection()->queryDB ( $query );
	}
	
	function getRandomProxy($proxies = null) {
		//echo ("Getting Random Proxy<b>");
		if (! isset ( $proxies )) {
			$proxies = $this->getProxyHostList ();
		}
		//echo ("Proxy Count: " . count ( $proxies ) . "<br>");
		//echo ('<br><br>');
		//var_dump ( $proxies );
		//echo ('<br><br>');
		//$proxy = $proxies [array_rand ( $proxies )];
		$proxy = array_shift ( $proxies );
		
		//$fullProxy = $proxy ["proxy"] . ":" . $proxy ["port"];
		//echo ("Proxy Selected : $fullProxy<br>");
		//pass = $this->testProxy ( $proxy );
		$pass = true; // TODO Remove and uncomment above
		

		if ($pass) {
			$this->ResetProxyErrorCount ( $proxy );
			return $proxy;
		} else {
			$this->deleteProxy ( $proxy );
			if (count ( $proxies ) > 0) {
				return $this->getRandomProxy ( $proxies );
			} else {
				return null;
			}
		}
	}
	
	function ResetProxyErrorCount($proxy) {
		$query = "Update proxies set errorCount=0 where proxy='" . $proxy ['proxy'] . "'";
		$this->getDBConnection()->queryDB ( $query );
	}
	
	function getProxyHostList() {
		$proxies = array ();
		//$query = "SELECT * FROM proxies where errorCount<3 ORDER BY RAND()";
		$query = "SELECT proxy, port FROM proxies where errorCount<3 ORDER BY RAND()";
		$results = $this->getDBConnection()->queryDB ( $query );
		while ( ($row = $results-> fetch_assoc()) ) {
			$proxies [] = $row;
		}
		return $proxies;
	}
}
/*
$obj = new Proxy ( );

$working = array ();
while ( count ( $working ) <= 2 ) {
	$proxy = $obj->getRandomProxy ();
	if (! isset ( $proxy )) {
		break;
	} else {
		$working [] = $proxy;
	}
	$working = array_unique ( $working );
}

echo ("# of Working Proxies: " . count ( $working ) . "<br>");

foreach ( $working as $proxy ) {
	$fullProxy = $proxy ["proxy"] . ":" . $proxy ["port"];
	echo ("Working Proxy: " . $fullProxy . "<br>");
}
*/

?>