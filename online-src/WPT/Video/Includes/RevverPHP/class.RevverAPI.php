<?php

/*
The RevverAPI class determines which method of packaging xmlrpc requests is to be
used, prepares and launches the method call and then returns the result as a standard
PHP variable. It requires curl support and will use the 'XMLRPC for PHP' library if
the built in XMLRPC functions for PHP are unavailable.

PHP
http://www.php.net/

XMLRPC
http://www.xmlrpc.com/

XMLRPC for PHP
http://www.php.net/xmlrpc
http://phpxmlrpc.sourceforge.net/
*/

class RevverAPI {
	function RevverAPI($url) {
		$this->url = $url;
		$this->curl = function_exists ( 'curl_init' );
		$this->xmlrpc = function_exists ( 'xmlrpc_encode_request' );
	}
	
	function callRemote($method) {
		// Curl is required so generate a fault if curl functions cannot be found.
		if (! $this->curl)
			return array ('faultCode' => - 1, 'faultString' => 'Curl functions are unavailable.' );
			// The first argument will always be the method name while all remaining arguments need
		// to be passed along with the call.
		$args = func_get_args ();
		array_shift ( $args );
		if ($this->xmlrpc) {
			// If php has xmlrpc support use the built in functions.
			$request = xmlrpc_encode_request ( $method, $args );
			$result = $this->__xmlrpc_call ( $request );
			$decodedResult = xmlrpc_decode ( $result );
		} else {
			// If no xmlrpc support is found, use the phpxmlrpc library. This involves containing
			// all variables inside the xmlrpcval class.
			$encapArgs = array ();
			foreach ( $args as $arg )
				$encapArgs [] = $this->__phpxmlrpc_encapsulate ( $arg );
			$msg = new xmlrpcmsg ( $method, $encapArgs );
			$client = new xmlrpc_client ( $this->url );
			$client->verifypeer = false;
			$result = $client->send ( $msg );
			if ($result->errno) {
				$decodedResult = array ('faultCode' => $result->errno, 'faultString' => $result->errstr );
			} else {
				$decodedResult = php_xmlrpc_decode ( $result->value () );
			}
		}
		return $decodedResult;
	}
	
	function __phpxmlrpc_encapsulate($arg) {
		// The class xmlrpcval is defined in the phpxmlrpc library. It requires both the variable
		// and the type. Dates are handled through the API as ISO 8601 string representations.
		if (is_string ( $arg )) {
			$encapArg = new xmlrpcval ( $arg, 'string' );
		} elseif (is_int ( $arg )) {
			$encapArg = new xmlrpcval ( $arg, 'int' );
		} elseif (is_bool ( $arg )) {
			$encapArg = new xmlrpcval ( $arg, 'boolean' );
		} elseif (is_array ( $arg )) {
			// The API server treats indexed arrays (lists) and associative arrays (dictionaries)
			// differently where in php they are essentially the same. Assuming that having a zero
			// index set indicates an indexed array is not perfect but should suffice for the
			// purpose of the API examples.
			if (isset ( $arg [0] )) {
				$array = array ();
				foreach ( $arg as $key => $value ) {
					$array [] = $this->__phpxmlrpc_encapsulate ( $value );
				}
				$encapArray = new xmlrpcval ( );
				$encapArray->addArray ( $array );
				$encapArg = $encapArray;
			} else {
				$struct = array ();
				foreach ( $arg as $key => $value ) {
					$struct [$key] = $this->__phpxmlrpc_encapsulate ( $value );
				}
				$encapStruct = new xmlrpcval ( );
				$encapStruct->addStruct ( $struct );
				$encapArg = $encapStruct;
			}
		} else {
			$encapArg = new xmlrpcval ( $arg, 'string' );
		}
		
		return $encapArg;
	}
	
	function __xmlrpc_call($request) {
		echo("Request: $request<br>");
		$header [] = "Content-type: text/xml";
		$header [] = "Content-length: " . strlen ( $request );
		$options = array (CURLOPT_RETURNTRANSFER => true, // return web page
CURLOPT_HEADER => false, // don't return headers
//CURLOPT_HTTPHEADER => $header, // header 
CURLOPT_POSTFIELDS => $request, // request 
CURLOPT_SSL_VERIFYPEER => FALSE, // verify peer
CURLOPT_SSL_VERIFYHOST => 0, // verify host 
CURLOPT_FOLLOWLOCATION => true, // follow redirects
CURLOPT_ENCODING => "", // handle all encodings
//CURLOPT_USERAGENT => "spider", // who am i
CURLOPT_AUTOREFERER => true, // set referer on redirect
CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
CURLOPT_TIMEOUT => 120, // timeout on response
CURLOPT_MAXREDIRS => 10 ); // stop after 10 redirects
		$ch = curl_init ( $this->url );
		curl_setopt_array ( $ch, $options );
		
		$data = curl_exec ( $ch );
		if (curl_errno ( $ch )) {
			// Curl errors are returned as emulated xmlrpc faults.
			$errorCurl = curl_error ( $ch );
			curl_close ( $ch );
			return xmlrpc_encode ( array ('faultCode' => - 1, 'faultString' => 'Curl Error : ' . $errorCurl ) );
		} else {
			curl_close ( $ch );
			return $data;
		}
	}

}

?>
