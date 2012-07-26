<?php

error_reporting(E_ALL);

require_once ("CBUtils/CBAbstract.php");

class WPTDaemon extends CBAbstract {

    public $processLimit;
    public $processTimeLimit;

    function __construct() {
        parent::__construct();
        exit(0);
    }

    function constructClass() {
        //echo("<hr>Daemon Started at ".date("m-d-y h:i:s A") . "\n<br>");
        $this -> processLimit = 10;
        $this -> processTimeLimit = 5;
        $this -> removeDeadProcessesFromQueue();
        $this -> runJobsInJobQueue();
        sleep(10);
    }

    // this causes infinite loop
    function __destruct() {
        $class = get_class($this);
        $file = $class . ".txt";
        $cmd = $class . ".php";
        $this -> getCommandLineHelper() -> startProcess($cmd, $file);
        parent::__destruct();
    }

    function removeDeadProcessesFromQueue() {
        $removeQuery = "delete from task where started is not null and (running=false or TIMESTAMPDIFF(MINUTE,started, now())>=" . $this -> processTimeLimit . ")";
        mysql_query($removeQuery);
        //mysql_query("COMMIT;");
        //$this->threadSafeQuery($removeQuery,"WRITE");
    }

    function runJobsInJobQueue() {
        //mysql_query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
        //mysql_query ( "SET TRANSACTION ISOLATION LEVEL REPEATABLE READ" );
        mysql_query ( "SET autocommit=1" );
        $runningTaskQuery = "Select id from task where running=true";
        $results = mysql_query($runningTaskQuery);
        //$results = $this->threadSafeQuery($runningTaskQuery);
        $runningProcessCount = mysql_num_rows($results);
        if ($runningProcessCount < $this -> processLimit) {
            $newProcessCount = $this -> processLimit - $runningProcessCount;
        } else {
            $newProcessCount = 0;
        }

        //echo(sprintf('Starting: %s new processes', $newProcessCount));

        $runJobsQuery = "select id, cmd, output from task where running=false and started is null and cmd is not null and output is not null limit " . $newProcessCount;
        $results = mysql_query($runJobsQuery);
        //$results = $this->threadSafeQuery($runJobsQuery);
        //echo ("runJobsInJobQueue Starting<br>\n\r");
        while (($row = mysql_fetch_assoc($results))) {
            $cmd = $row['cmd'];
            $output = $row['output'];
            $taskID = $row['id'];
            $this -> getCommandLineHelper() -> startProcess($cmd, $output);
            mysql_query("delete from task where id=$taskID");
        }
        //mysql_query ( "COMMIT;" );
        //mysql_query("SET autocommit=1");
    }

}

$daemon = new WPTDaemon();
