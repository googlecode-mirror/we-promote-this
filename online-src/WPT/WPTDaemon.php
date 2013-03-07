<?php

//error_reporting(E_ALL);

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

        // Is this the only Daemon Running
        if ($this -> amITheOnlyOne()) {
            $this -> runJobsInJobQueue();
            sleep(10);
        }
    }

    function amITheOnlyOne() {
        $query = "Select id from task where class='" . get_class($this) . "' Order by id asc";
        $result = $this->getDBConnection()->queryDB($query);
        $count = $result->num_rows;
        $row = $result-> fetch_assoc();
        $oldestTaskID = $row['id'];
        $original = false;
        // If not only run if I'm the oldest one
        if ($count > 1) {
            if ($this -> taskID == $oldestTaskID) {
                $original = true;
            }
        } else {
            $original = true;
        }
        return $original;
    }

    // this causes infinite loop
    function __destruct() {
        if ($this -> amITheOnlyOne()) {
            $class = get_class($this);
            $file = $class . ".txt";
            $cmd = $class . ".php";
            $this -> getCommandLineHelper() -> startProcess($cmd, $file);
        }
        parent::__destruct();
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
        $this->getDBConnection()->queryDB("SET autocommit=1");
        $runningTaskQuery = "Select id from task where running=true";
        $results = $this->getDBConnection()->queryDB($runningTaskQuery);
        //$results = $this->threadSafeQuery($runningTaskQuery);
        $runningProcessCount = $results->num_rows;
        if ($runningProcessCount < $this -> processLimit) {
            $newProcessCount = $this -> processLimit - $runningProcessCount;
        } else {
            $newProcessCount = 0;
        }

        //echo(sprintf('Starting: %s new processes', $newProcessCount));

        $runJobsQuery = "select id, cmd, output from task where running=false and started is null and cmd is not null and output is not null limit " . $newProcessCount;
        $results = $this->getDBConnection()->queryDB($runJobsQuery);
        //$results = $this->threadSafeQuery($runJobsQuery);
        //echo ("runJobsInJobQueue Starting<br>\n\r");
        while (($row = $results-> fetch_assoc())) {
            $cmd = $row['cmd'];
            $output = $row['output'];
            $taskID = $row['id'];
            $this -> getCommandLineHelper() -> startProcess($cmd, $output);
            $this->getDBConnection()->queryDB("delete from task where id=$taskID");
        }
        //$this->getDBConnection()->queryDB ( "COMMIT;" );
        //$this->getDBConnection()->queryDB("SET autocommit=1");
    }

}

$daemon = new WPTDaemon();
