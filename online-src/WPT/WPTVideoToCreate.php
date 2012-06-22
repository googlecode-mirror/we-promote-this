<?php

require_once ("CBUtils/CBAbstract.php");

class WPTVVideoToCreate extends CBAbstract {
	
	function constructClass() {
		
		// Query Database and decide what video to create
		$pid = "ETVCORP";
		echo ($pid);
	}
	
	function __destruct() {
		parent::__destruct ();
		/*
		$logFile = get_class ( $this ) . "_logfile.html";
		$f = fopen ( $logFile, "w" );
		fwrite ( $f, $this->getOutputContent() );
		fclose ( $f );
		exec ( "start " . $logFile );
		*/
	}
}

$wpe = new WPTVVideoToCreate ( );

?>