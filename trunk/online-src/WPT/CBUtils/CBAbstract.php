<?php
// error_reporting ( E_ALL ); // all errors
// ror_reporting ( E_ALL ^ E_NOTICE ); // turn on all errors, warnings minus
// notices
error_reporting ( E_ERROR ); // Errors only

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
	private $taskID;
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
			self::$ConfigParser = new ConfigParser ();
		}
		
		if (! isset ( self::$DBConnection )) {
			$this->reconnectDB ();
		}
		
		if (! isset ( self::$CommandLineHelper )) {
			self::$CommandLineHelper = new CommandLineHelper ();
		}
		
		if (! isset ( self::$Logger )) {
			self::$Logger = new LogHelper ();
		}
		
		if (ONLINEMODE) {
			$this->notifyDBOfTask ();
		}
		$this->constructClass ();
		
		if (ONLINEMODE) {
			$this->notifyDBOfTaskFinished ();
		}
	}
	function __destruct() {
		$this->getCommandLineHelper ()->__destruct ();
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
		$query = "insert into task (class,running,started) values ('" . get_class ( $this ) . "',true,now())";
		mysql_query ( "LOCK TABLES task LOW_PRIORITY WRITE" );
		mysql_query ( $query );
		$this->taskID = mysql_insert_id ();
		mysql_query ( "UNLOCK TABLES;" );
	}
	function notifyDBOfTaskFinished() {
		// echo ("Notifying DB Task Finished of id: " . $this->taskID . "<br>");
		mysql_query ( "LOCK TABLES task LOW_PRIORITY WRITE" );
		$query = "update task set running=false where id=" . $this->taskID;
		mysql_query ( "UNLOCK TABLES;" );
		mysql_query ( $query );
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
