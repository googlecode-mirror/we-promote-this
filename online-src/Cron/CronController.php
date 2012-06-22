<?php
require_once 'CronAbstract.php';
class CronController extends CronAbstract {
	
	public $cronClassName;
	
	function __construct($cronClassName = null) {
		if (isset ( $cronClassName )) {
			$this->cronClassName = $cronClassName;
		} else if (isset ( $_REQUEST ['class'] )) {
			$this->cronClassName = $_REQUEST ['class'];
		} else {
			$this->handleARGV ();
		}
		parent::__construct ();
	}
	
	function handleARGV() {
		global $argv;
		if (isset ( $argv ) & count ( $argv ) > 1) {
			array_shift ( $argv );
			foreach ( $argv as $value ) {
				$keyArray = split ( "=", $value );
				$key = $keyArray [0];
				$keyValue = $keyArray [1];
				switch ($key) {
					case "class" :
						$this->cronClassName = $keyValue;
						break;
				}
			}
		}
	}
	
	function runCron() {
		if (isset ( $this->cronClassName )) {
			$file = $this->cronClassName;
			$output = $this->getCommandLineHelper ()->run_in_background ( $file . ".php", $file . ".txt" );
			$url = "../WPT/CBUtils/ShowLog.php?log=$output";
			echo ("View <a href='$url'>$file </a> Log");
		}
	}

}

if (isset ( $_REQUEST ['class'] )) {
	new CronController ( );
}
?>