<?php
 error_reporting ( E_ALL ); // all errors
// ror_reporting ( E_ALL ^ E_NOTICE ); // turn on all errors, warnings minus
// notices
//error_reporting ( E_ERROR ); // Errors only


set_time_limit ( 300 ); // 5 Minutes
ob_start ();
$prependFile = '/home/content/50/6934650/html/pear/includes/prepend.php';
if (file_exists ( $prependFile )) {
	include_once $prependFile;
}

require_once ("ConfigParser.php");
require_once ("DBConnection.php");
require_once ("CommandLineHelper.php");
require_once ("LogHelper.php");
abstract class CBAbstract {
	public static $ConfigParser;
	public static $DBConnection;
	public static $CommandLineHelper;
	public static $Logger;
	public $taskID;
	private $outputContent;
	function __construct() {
		$path = dirname ( __FILE__ );
		// $path = '/home/content/50/6934650/html/';
		$onlineMode = false;
		
		if (stripos ( $path, '/home/content/50/6934650/html/' ) !== false) {
			$onlineMode = true;
		}
		if (! defined ( 'ONLINEMODE' )) {
			define ( 'ONLINEMODE', $onlineMode );
		}
		
		if (! isset ( self::$ConfigParser )) {
			self::$ConfigParser = new ConfigParser ( );
		}
		
		if (! isset ( self::$DBConnection )) {
			$this->reconnectDB ();
		}
		
		if (! isset ( self::$CommandLineHelper )) {
			self::$CommandLineHelper = new CommandLineHelper ( self::$DBConnection );
		}
		
		if (! isset ( self::$Logger )) {
			self::$Logger = new LogHelper ( );
		}
		
		if (ONLINEMODE) {
			$this->notifyDBOfTask ();
		}
		$this->constructClass ();
		
		if (ONLINEMODE) {
			$this->notifyDBOfTaskFinished ();
		}
		echo (" ");
	}
	function __destruct() {
		$this->getCommandLineHelper ()->__destruct ();
		$this->getDBConnection ()->__destruct ();
		$this->outputContent = ob_get_contents (); // Capture all the output and
		// display it when class is
		// finished
		ob_end_clean ();
		echo ($this->outputContent);
	}
	function getOutputContent() {
		return $this->outputContent;
	}
	function notifyDBOfTask() {
		// SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
		//$this->getDBConnection()->queryDB("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE");
		// Turn auto commit off
		//$this->getDBConnection()->queryDB("SET autocommit=0");
		

		//$this->getDBConnection()->queryDB("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
		// Turn auto commit on
		//$this->getDBConnection()->queryDB("SET autocommit=1");
		

		$query = "insert into task (class,running,started) values ('" . get_class ( $this ) . "',true,now())";
		//$this->getDBConnection()->threadSafeQuery($query,"WRITE");
		$this->getDBConnection ()->queryDB ( $query );
		//$this->getDBConnection()->queryDB("COMMIT;");
		$this->taskID = $this->getDBConnection ()->getDBConnection ()->insert_id;
	}
	function notifyDBOfTaskFinished() {
		// echo ("Notifying DB Task Finished of id: " . $this->taskID . "<br>");
		$query = "update task set running=false where id=" . $this->taskID;
		//$this->getDBConnection()->threadSafeQuery($query,"WRITE");
		$this->getDBConnection ()->queryDB ( $query );
		//$this->getDBConnection()->queryDB("COMMIT;");
	// Turn auto commit on
	//$this->getDBConnection()->queryDB("SET autocommit=1");
	//$this->getDBConnection()->queryDB("SET TRANSACTION ISOLATION LEVEL REPEATABLE READ");
	}
	
	function reconnectCon($con, $tries = 3) {
		// Check if connection is open and return if you can reconnect
		if ($con->ping ()) {
			return $con;
		} else {
			// Get the tread ID
			$threadID = $con->thread_id;
			// Find out which connection it is and reset it then return it
			if ($this->getDBConnection ()->getDBConnection ()->thread_id == $threadID) {
			
			}
		}
	}
	
	function reconnectDB() {
		if (ONLINEMODE) {
			self::$DBConnection = new DBConnection ( mysqlServerIP2, dbname, dbuser, dbpassword, wpip, wpdbname, wpdbuser, wpdbpassword );
		} else {
			self::$DBConnection = new DBConnection ( localmysqlServerIP2, localdbname, localdbuser, localdbpassword, wpip, wpdbname, wpdbuser, wpdbpassword );
		}
	}
	static function getConfigParser() {
		return self::$ConfigParser;
	}
	static function getCommandLineHelper() {
		return self::$CommandLineHelper;
	}
	static function getDBConnection() {
		return self::$DBConnection;
	}
	static function getLogger() {
		return self::$Logger;
	}
	function __toString() {
		$classVars = get_class_vars ( get_class ( $this ) );
		$vars = '';
		foreach ( $classVars as $name => $value ) {
			$value = $this->$name;
			if (is_array ( $value )) {
				$value = implode ( ",", $value );
			}
			$vars .= "<b>$name</b> : $value\n<br>";
		}
		return $vars;
	}
	abstract function constructClass();
}
?>

