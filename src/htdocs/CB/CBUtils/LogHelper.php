<?php
require_once 'Log.php';
class LogHelper {
	public $pearLogger;
	function __construct() {
		// create Log object
		//$this->pearLogger = &Log::singleton ( "sql", "log_table", __FILE__, array ('dsn' => "mysql://" . dbuser . ":" . dbpassword . "@" . mysqlServerIP . "/" . dbname, 'identLimit' => 300 ) );

		// use a lenient permission mask, and format entries accordingly
		$logconf = array('mode' => 0775, 'timeFormat' => '%X %x');
		$this -> pearLogger = &Log::singleton("file", dirname(__FILE__) . "../../Log/wpt_log_" . date("Ymdhis") . ".txt", 'wpt', $logconf);
		$mask = Log::MIN(PEAR_LOG_ERR);
		$this -> pearLogger -> setMask($mask);
		set_error_handler(array($this, 'errorHandler'));
		set_exception_handler(array($this, 'exceptionHandler'));
	}

	function logInfo($message) {
		$this -> log($message, PEAR_LOG_INFO);
		echo($message . "<br>\n\r");
	}

	function log($message, $level) {
		//$this -> pearLogger -> setIdent(__FILE__);
		$this -> pearLogger -> setIdent('LogHelper.php');
		$this -> pearLogger -> log($message, $level);
	}

	function errorHandler($code, $message, $file, $line) {
		/* Map the PHP error to a Log priority. */
		switch ($code) {
			case E_WARNING :
			case E_USER_WARNING :
				$priority = PEAR_LOG_WARNING;
				break;
			case E_NOTICE :
			case E_USER_NOTICE :
				$priority = PEAR_LOG_NOTICE;
				break;
			case E_ERROR :
			case E_USER_ERROR :
				$priority = PEAR_LOG_ERR;
				break;
			default :
				$priority = PEAR_LOG_EMERG;
		}
		$this -> log($message . ' in ' . $file . ' at line ' . $line, $priority);
	}

	function exceptionHandler($exception) {
		$this -> log($exception -> getMessage(), PEAR_LOG_ALERT);
	}

}

//$logger = new LogHelper ( );
?>