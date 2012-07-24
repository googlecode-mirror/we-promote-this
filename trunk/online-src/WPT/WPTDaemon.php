<?php

/**
 * In panic situations, you can always kill you daemon by typing
 *
 * killall -9 WPTDaemon.php
 * OR:
 * killall -9 php
 *
 */

// Make it possible to test in source directory
// This is for PEAR developers only
//ini_set('include_path', ini_get('include_path').':..');


error_reporting ( E_STRICT );

require_once ("CBUtils/CBAbstract.php");
// Include Class
require_once 'System/Daemon.php';

class WPTDaemon extends CBAbstract {
	
	public $runmode;
	public $processLimit;
	public $processTimeLimit;
	
	function __construct() {
		parent::__construct ();
	}
	
	function constructClass() {
		$this->handleARGV ();
		echo ("Started Class\n<br>");
		$this->processLimit = 10;
		$this->processTimeLimit = 5;
		
		// Setup
		$options = array ('appName' => 'WPTDaemon', 'appDir' => dirname ( __FILE__ ), 'appDescription' => 'Runs tasks placed into the task table of the database', 'authorName' => 'Christopher D. Queen', 'authorEmail' => 'chrisqueen@hotmail.com', 'sysMaxExecutionTime' => '0', 'sysMaxInputTime' => '0', 'sysMemoryLimit' => '1024M', 'appRunAsGID' => 1011, 'appRunAsUID' => 1011 );
		
		System_Daemon::setOptions ( $options );
		
		// This program can also be run in the forground with runmode --no-daemon
		if (! $this->runmode ['no-daemon']) {
			echo ("About to Spawn Daemon\n<br>");
			// Spawn Daemon
			System_Daemon::start ();
			echo ("Spawned Daemon\n<br>");
		}
		
		// Test after spawn
		$this->waitForLockAndWrite ( dirname ( __FILE__ ) . "/WPTDaemon_spawned.txt", "I was spawned\n" );
		
		// With the runmode --write-initd, this program can automatically write a
		// system startup file called: 'init.d'
		// This will make sure your daemon will be started on reboot
		if (! $this->runmode ['write-initd']) {
			System_Daemon::info ( 'not writing an init.d script this time' );
		} else {
			if (($initd_location = System_Daemon::writeAutoRun ()) === false) {
				System_Daemon::notice ( 'unable to write init.d script' );
			} else {
				System_Daemon::info ( 'sucessfully written startup script: %s', $initd_location );
			}
		}
		
		// This variable gives your own code the ability to breakdown the daemon:
		$runningOkay = true;
		
		// What mode are we in?
		$mode = '"' . (System_Daemon::isInBackground () ? '' : 'non-') . 'daemon" mode';
		
		// Log something using the Daemon class's logging facility
		// Depending on runmode it will either end up:
		//  - In the /var/log/logparser.log
		//  - On screen (in case we're not a daemon yet)
		System_Daemon::info ( '{appName} running in %s | Started @ %s', $mode, date ( "m/d/Y h:i:s A" ) );
		
		// While checks on 2 things in this case:
		// - That the Daemon Class hasn't reported it's dying
		// - That your own code has been running Okay
		while ( ! System_Daemon::isDying () && $runningOkay ) {
			
			//Reconnect to the Database
			$this->reconnectDB ();
			
			// In the actuall logparser program, You could replace 'true'
			// With e.g. a  parseLog('vsftpd') function, and have it return
			// either true on success, or false on failure.
			//$runningOkay = true;
			$db = $this->getDBConnection ();
			if (! isset ( $db )) {
				$runningOkay = false;
			}
			
			// Should your runJobsInJobQueue return false, then
			// the daemon is automatically shut down.
			// An extra log entry would be nice, we're using level 3,
			// which is critical.
			// Level 4 would be fatal and shuts down the daemon immediately,
			// which in this case is handled by the while condition.
			if (! $runningOkay) {
				System_Daemon::err ( 'Error: No DB connection, ' . 'so this will be my last run' );
			} else {
				$this->removeDeadProcessesFromQueue ();
				$this->runJobsInJobQueue ();
			}
			
			// Relax the system by sleeping for a little bit
			// iterate also clears statcache
			System_Daemon::iterate ( 10 );
		}
		
		// Shut down the daemon nicely
		// This is ignored if the class is actually running in the foreground
		System_Daemon::stop ();
	
	}
	
	function waitForLockAndWrite($file, $txt) {
		$fp = fopen ( $file, "a" );
		fwrite ( $fp, $txt . "\n" );
		fclose ( $fp );
	}
	
	function handleARGV() {
		global $argv;
		
		// Allowed arguments & their defaults
		$this->runmode = array ('no-daemon' => false, 'help' => false, 'write-initd' => false );
		
		// Scan command line attributes for allowed arguments
		foreach ( $argv as $k => $arg ) {
			if (substr ( $arg, 0, 2 ) == '--' && isset ( $this->runmode [substr ( $arg, 2 )] )) {
				$this->runmode [substr ( $arg, 2 )] = true;
			}
		}
		
		// Help mode. Shows allowed argumentents and quit directly
		if ($this->runmode ['help'] == true) {
			echo 'Usage: ' . $argv [0] . ' [runmode]' . "\n";
			echo 'Available runmodes:' . "\n";
			foreach ( $this->runmode as $runmod => $val ) {
				echo ' --' . $runmod . "\n";
			}
			die ();
		}
	}
	
	function removeDeadProcessesFromQueue() {
		$removeQuery = "delete from task where started is not null and (running=false or TIMESTAMPDIFF(MINUTE,started, now())>=" . $this->processTimeLimit . ")";
		mysql_query ( $removeQuery );
		//mysql_query("COMMIT;");
	//$this->threadSafeQuery($removeQuery,"WRITE");
	}
	
	function runJobsInJobQueue() {
		//mysql_query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
		//mysql_query ( "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ" );
		//mysql_query ( "SET autocommit=0" );
		$runningTaskQuery = "Select id from task where running=true";
		$results = mysql_query ( $runningTaskQuery );
		//$results = $this->threadSafeQuery($runningTaskQuery);
		$runningProcessCount = mysql_num_rows ( $results );
		if ($runningProcessCount < $this->processLimit) {
			$newProcessCount = $this->processLimit - $runningProcessCount;
		} else {
			$newProcessCount = 0;
		}
		
		System_Daemon::info ( 'Starting: %s new processes', $newProcessCount );
		
		$runJobsQuery = "select id, cmd, output from task where running=false and started is null and cmd is not null and output is not null limit " . $newProcessCount;
		$results = mysql_query ( $runJobsQuery );
		//$results = $this->threadSafeQuery($runJobsQuery);
		//echo ("runJobsInJobQueue Starting<br>\n\r");
		while ( ($row = mysql_fetch_assoc ( $results )) ) {
			$cmd = $row ['cmd'];
			$output = $row ['output'];
			$taskID = $row ['id'];
			$this->getCommandLineHelper ()->startProcess ( $cmd, $output, $taskID );
			mysql_query ( "delete from task where id=$taskID" );
		}
		//mysql_query ( "COMMIT;" );
	//mysql_query("SET autocommit=1");
	}
}

$obj = new WPTDaemon ( );