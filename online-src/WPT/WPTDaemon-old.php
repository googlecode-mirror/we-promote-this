<?php

/**
 * In panic situations, you can always kill you daemon by typing
 *
 * killall -9 WPTDaemon.php
 * OR:
 * killall -9 php
 *
 */
 
error_reporting(E_ALL);

require_once ("CBUtils/CBAbstract.php");

// Make it possible to test in source directory
// This is for PEAR developers only
ini_set('include_path', ini_get('include_path') . ':..');

// Include Class
require_once 'System/Daemon.php';

class WPTDaemon extends CBAbstract {

    public $processLimit;
    public $processTimeLimit;
    public $runningOkay;

    function __construct() {
        parent::__construct();
    }

    function constructClass() {
        echo("Started Class\n<br>");
        $this -> processLimit = 10;
        $this -> processTimeLimit = 5;

        // Test after spawn
        $this -> waitForLockAndWrite(dirname(__FILE__) . "/WPTDaemon_spawned.txt", "I was spawned\n");

        // This variable gives your own code the ability to breakdown the daemon:
        $this -> runningOkay = true;
    }

    function isRunningOk() {
        return $this -> runningOkay;
    }

    function waitForLockAndWrite($file, $txt) {
        $fp = fopen($file, "a");
        fwrite($fp, $txt . "\n");
        fclose($fp);
    }

    function removeDeadProcessesFromQueue() {
        $removeQuery = "delete from task where started is not null and (running=false or TIMESTAMPDIFF(MINUTE,started, now())>=" . $this -> processTimeLimit . ")";
        $this->getDBConnection()->queryDB($removeQuery);
        //$this->getDBConnection()->queryDB("COMMIT;");
        //$this->threadSafeQuery($removeQuery,"WRITE");
    }

    function runJobsInJobQueue() {
        //$this->getDBConnection()->queryDB("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
        //$this->getDBConnection()->queryDB ( "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ" );
        //$this->getDBConnection()->queryDB ( "SET autocommit=0" );
        $runningTaskQuery = "Select id from task where running=true";
        $results = $this->getDBConnection()->queryDB($runningTaskQuery);
        //$results = $this->threadSafeQuery($runningTaskQuery);
        $runningProcessCount = $results->num_rows;
        if ($runningProcessCount < $this -> processLimit) {
            $newProcessCount = $this -> processLimit - $runningProcessCount;
        } else {
            $newProcessCount = 0;
        }

        System_Daemon::info('Starting: %s new processes', $newProcessCount);

        $runJobsQuery = "select id, cmd, output from task where running=false and started is null and cmd is not null and output is not null limit " . $newProcessCount;
        $results = $this->getDBConnection()->queryDB($runJobsQuery);
        //$results = $this->threadSafeQuery($runJobsQuery);
        //echo ("runJobsInJobQueue Starting<br>\n\r");
        while (($row = $results-> fetch_assoc())) {
            $cmd = $row['cmd'];
            $output = $row['output'];
            $taskID = $row['id'];
            $this -> getCommandLineHelper() -> startProcess($cmd, $output, $taskID);
            $this->getDBConnection()->queryDB("delete from task where id=$taskID");
        }
        //$this->getDBConnection()->queryDB ( "COMMIT;" );
        //$this->getDBConnection()->queryDB("SET autocommit=1");
    }

}

// Allowed arguments & their defaults
$runmode = array('no-daemon' => false, 'help' => false, 'write-initd' => false);

// Scan command line attributes for allowed arguments
foreach ($argv as $k => $arg) {
    if (substr($arg, 0, 2) == '--' && isset($runmode[substr($arg, 2)])) {
        $runmode[substr($arg, 2)] = true;
    }
}

// Help mode. Shows allowed argumentents and quit directly
if ($runmode['help'] == true) {
    echo 'Usage: ' . $argv[0] . ' [runmode]' . "\n";
    echo 'Available runmodes:' . "\n";
    foreach ($this->runmode as $runmod => $val) {
        echo ' --' . $runmod . "\n";
    }
    die();
}

// Setup
$options = array('appName' => 'WPTDaemon', 'appDir' => dirname(__FILE__), 'appDescription' => 'Runs tasks placed into the task table of the database', 'authorName' => 'Christopher D. Queen', 'authorEmail' => 'chrisqueen@hotmail.com', 'sysMaxExecutionTime' => '0', 'sysMaxInputTime' => '0', 'sysMemoryLimit' => '1024M', 'appRunAsGID' => 1011, 'appRunAsUID' => 1011);

System_Daemon::setOptions($options);

System_Daemon::log(System_Daemon::LOG_INFO, "Daemon not yet started so " . "this will be written on-screen");

// This program can also be run in the forground with runmode --no-daemon
if (!$runmode['no-daemon']) {
    echo("About to Spawn Daemon\n<br>");
    // Spawn Daemon
    System_Daemon::start();
    System_Daemon::log(System_Daemon::LOG_INFO, "Daemon: '" . System_Daemon::getOption("appName") . "' spawned! This will be written to " . System_Daemon::getOption("logLocation"));
}

$daemon = new WPTDaemon();

// What mode are we in?
$mode = '"' . (System_Daemon::isInBackground() ? '' : 'non-') . 'daemon" mode';

// Log something using the Daemon class's logging facility
// Depending on runmode it will either end up:
//  - In the /var/log/logparser.log
//  - On screen (in case we're not a daemon yet)
System_Daemon::info('{appName} running in %s | Started @ %s', $mode, date("m/d/Y h:i:s A"));

// While checks on 2 things in this case:
// - That the Daemon Class hasn't reported it's dying
// - That your own code has been running Okay
while (!System_Daemon::isDying() && $daemon -> isRunningOk()) {

    $daemon -> runningOkay = true;

    $db = $daemon -> getDBConnection();
    $tries = 3;
    do {
        //Reconnect to the Database
        $daemon -> reconnectDB();
        $tries++;
    } while(!isset($db) && $tries<3);

    if (!isset($db)) {
        $daemon -> runningOkay = false;
    }

    // Should your runJobsInJobQueue return false, then
    // the daemon is automatically shut down.
    // An extra log entry would be nice, we're using level 3,
    // which is critical.
    // Level 4 would be fatal and shuts down the daemon immediately,
    // which in this case is handled by the while condition.
    if (!$daemon -> isRunningOk()) {
        System_Daemon::err('Error: No DB connection, ' . 'so this will be my last run');
    } else {
        $daemon -> removeDeadProcessesFromQueue();
        $daemon -> runJobsInJobQueue();
    }

    // Relax the system by sleeping for a little bit
    // iterate also clears statcache
    System_Daemon::iterate(2);
}

// Shut down the daemon nicely
// This is ignored if the class is actually running in the foreground
System_Daemon::stop();
