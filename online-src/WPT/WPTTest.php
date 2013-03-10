<?php
// turn on all errors
error_reporting(E_ALL);
// turn off all errors
//error_reporting ( 0 );
require_once 'CBUtils/CBAbstract.php';
class WPTTest extends CBAbstract {

	function constructClass() {
		$this->getLogger ()->logInfo ( "Testing my log info" );
		trigger_error ( 'Testing trigger error', E_USER_ERROR );
		throw new Exception('Uncaught Exception');
		echo "Not Executed\n";
	}

}

$test = new WPTTest ( );
?>