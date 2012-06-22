<?php
//require_once '/home/content/50/6934650/html/pear/includes/prepend.php';
$path = realpath ( dirname ( __FILE__ ) . "/../" );
set_include_path ( get_include_path () . PATH_SEPARATOR . $path );
require_once 'WPT/CBUtils/CBAbstract.php';
abstract class CronAbstract extends CBAbstract {
	function constructClass() {
		$this->runCron ();
		echo ("\n<br>");
	}
	function __construct() {
		parent::__construct ();
	}
	function __destruct() {
		parent::__destruct ();
	}
	abstract function runCron();
}
?>