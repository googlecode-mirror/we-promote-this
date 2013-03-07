<?php
require_once 'CronAbstract.php';

class CronClearTask extends CronAbstract {
	function runCron() {
	    $this->getDBConnection()->queryDB( "TRUNCATE TABLE task;" ); // Clear all running and scheduled task from task table
	}
}
new CronClearTask ( );

?>