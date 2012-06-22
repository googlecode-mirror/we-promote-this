<?php
require_once 'CronAbstract.php';

class CronClearTask extends CronAbstract {
	function runCron() {
		mysql_query ( "TRUNCATE TABLE task;" ); // Clear all running and scheduled task from task table
	}
}
new CronClearTask ( );

?>