<?php
error_reporting ( E_ALL); // all errors
//error_reporting ( E_ALL ^ E_NOTICE ); // turn on all errors, warnings minus notices
//error_reporting(E_ERROR); // Errors only
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
	
		if (! isset ( self::$ConfigParser )) {
			self::$ConfigParser = new ConfigParser ( );
		}
		if (! isset ( self::$DBConnection )) {
			$this->reconnectDB ();
		}
		
		die ('before command line');
		
		if (! isset ( self::$CommandLineHelper )) {
		
			self::$CommandLineHelper = new CommandLineHelper ( );
			die("after command line");
		}
		
		die("after command outside if");
		
		die ('before logger');
		
		if (! isset ( self::$Logger )) {
			self::$Logger = new LogHelper ( );
		}
		echo ('before notify');
		
		if (ONLINEMODE) {
			$this->notifyDBOfTask ();
		}
		echo ('after notify');
		echo ('before construct');
		
		$this->constructClass ();
		echo ('after construct');
		if (ONLINEMODE) {
			$this->notifyDBOfTaskFinished ();
		}
	}
	function __destruct() {
		if (ONLINEMODE) {
			$this->notifyDBOfTaskFinished ();
		}
		$this->getCommandLineHelper ()->__destruct ();
		$this->outputContent = ob_get_contents (); // Capture all the output and display it when class is finished
		ob_end_clean ();
		//echo ($this->outputContent);
	}
	
	function getOutputContent() {
		return $this->outputContent;
	}
	
	function notifyDBOfTask() {
		$query = "insert into task (class,running,started) values ('" . get_class ( $this ) . "',true,now())";
		mysql_query ( $query );
		$this->taskID = mysql_insert_id ();
	}
	function notifyDBOfTaskFinished() {
		$query = "update task set running=false where id=" . $this->taskID;
		mysql_query ( $query );
	}
	function reconnectDB() {
		self::$DBConnection = new DBConnection ( mysqlServerIP2, dbname, dbuser, dbpassword );
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

