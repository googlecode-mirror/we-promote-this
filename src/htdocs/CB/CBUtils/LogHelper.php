<?php
require_once 'Log.php';
class LogHelper {
	public $pearLogger;
	function __construct() {
		// create Log object
		$this->pearLogger = &Log::singleton ( "sql", "log_table", __FILE__, array ('dsn' => "mysql://" . dbuser . ":" . dbpassword . "@" . mysqlServerIP . "/" . dbname, 'identLimit' => 300 ) );
		$mask = Log::MIN ( PEAR_LOG_ERR );
		$this->pearLogger->setMask ( $mask );
		set_error_handler ( array ($this, 'errorHandler' ) );
		set_exception_handler ( array ($this, 'exceptionHandler' ) );
	}
	function logInfo($message) {
		$caller = $this->getCallingMethodTrace ();
		$file = $caller ['file'];
		$this->log ( $message, PEAR_LOG_INFO, $file );
		echo ($message . "<br>\n\r");
	}
	function log($message, $level, $file = null) {
		if (! isset ( $file )) {
			$caller = $this->getCallingMethodTrace ();
			$file = $caller ['file'];
		}
		$this->pearLogger->setIdent ( $file );
		$this->pearLogger->log ( $message, $level );
	}
	function getCallingMethodTrace() {
		$e = new Exception ( );
		$trace = $e->getTrace ();
		//position 0 would be the line that called this function so we ignore it
		$last_call = $trace [1];
		//$callers = debug_backtrace ();
		//$last_call = $callers[1];
		return $last_call;
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
		$this->log ( $message . ' in ' . $file . ' at line ' . $line, $priority, $file );
	}
	function exceptionHandler($exception) {
		try {
			$this->log ( $exception->getMessage (), PEAR_LOG_ALERT, $exception->getFile () );
		} catch ( Exception $e ) {
			print get_class ( $e ) . " thrown within the exception handler. Message: " . $e->getMessage () . " on line " . $e->getLine ();
		}
	}
}
//$logger = new LogHelper ( );
?>