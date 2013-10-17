<?php
error_reporting(E_ALL ^ E_NOTICE);
// turn on all errors, warnings minus notices
//error_reporting ( E_ALL); // turn on all errors
//error_reporting(E_ERROR); // Errors only
set_time_limit(60 * 60);
// 1 hour
ob_start();

//require_once ("ConfigParser.php");
require_once ("config.php");
require_once ("ConfigParser.php");
require_once ("DBConnection.php");
require_once ("CommandLineHelper.php");
require_once ("LogHelper.php");
abstract class CBAbstract {
    public static $ConfigParser;
    public static $DBConnection;
    public static $CommandLineHelper;
    public static $Logger;
    private $outputContent;

    function __construct() {

        if (!isset(self::$ConfigParser)) {
            self::$ConfigParser = new ConfigParser();
        }
        if (!isset(self::$DBConnection)) {
            $this -> reconnectDB();
        }
        if (!isset(self::$CommandLineHelper)) {
            self::$CommandLineHelper = new CommandLineHelper();
        }
        if (!isset(self::$Logger)) {
            self::$Logger = new LogHelper();
        }
        $this -> constructClass();
    }

    function __destruct() {
        $this -> getCommandLineHelper() -> __destruct();
        $this -> outputContent = ob_get_contents();
        // Capture all the output and display it when class is finished
        // Dump final output
        if ($this -> outputContent !== false) {
            $this -> getLogger() -> logInfo($this -> outputContent);
            echo($this -> outputContent . "<br><br>");
        }
        ob_end_clean();
    }

    function getOutputContent() {
        return $this -> outputContent;
    }

    function reconnectDB() {
        self::$DBConnection = new DBConnection(mysqlServerIP2, dbname, dbuser, dbpassword);
    }
	
	 function runQuery($query, $con, $returnAffectedRows = false, $retry = 3) {
        $affectedRowCount = 0;
        $results = $this -> getDBConnection() -> queryCon($query, $con);
        $affectedRowCount = $con -> affected_rows;
        if ($con -> errno) {
            if ($retry > 0 && $con -> errno == 2006) {
                //sleep(10 + rand(0, 15));
                if (!$con -> ping()) {
                    $this -> reconnectDB();
                    $con = $this -> getDBConnection() -> getMatchingCon($con);
                }
                return $this -> runQuery($query, $con, $returnAffectedRows, --$retry);

            } else {
                $this -> getLogger() -> log('Couldnt execute query: ' . $query . '<br>Mysql Error (' . $con -> errno . '): ' . $con -> error . " | Retries: " . (3 - $retry), PEAR_LOG_ERR);
            }
        }
        if ($returnAffectedRows) {
            return $affectedRowCount;
        } else {
            return $results;
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
        $classVars = get_class_vars(get_class($this));
        $vars = '';
        foreach ($classVars as $name => $value) {
            $value = $this -> $name;
            if (is_array($value)) {
                $value = implode(",", $value);
            }
            $vars .= "<b>$name</b> : $value\n<br>";
        }
        return $vars;
    }

    abstract function constructClass();
}
?>

