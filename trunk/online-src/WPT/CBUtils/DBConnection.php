<?php
/**
 * Author: Christopher D. Queen
 * Created: Jan 11, 2009
 * Description: DBConnection.php is used to establish a connection with the database
 * The connection created by this file also allows other php script to perform operations
 * on the connected database

 * * Class: DBConnection
 * This class  uses the ConfigParser class to read the database values from the config file and stores
 * those values to local variables $ip,$port,$user,$password,$dbName
 * It gives access to performing queries to a database
 */
class DBConnection {
    public $ip;
    // IP address of the database
    public $dbUser;
    // user name to login to database
    public $dbPassword;
    // password to login to database
    public $dbName;
    // name of the database to use
    public $con;
    public $wpCon;
    public $connected;
    public $mysqliCon;

    /*?
     * Function: DBConnection
     * This is the constructor for DBConnection.
     * It uses the config file on the server and stores the database values to local variables
     */
    function __construct($ip, $dbName, $dbUser, $dbPassword, $wpip = null, $wpdbname = null, $wpdbuser = null, $wpdbpassword = null) {
        if (isset($wpip) && isset($wpdbname) && isset($wpdbuser) && isset($wpdbpassword)) {
            $this -> wpCon = $this -> connectToDB($wpip, $wpdbuser, $wpdbpassword, $wpdbname);
        }
        $this -> ip = $ip;
        $this -> dbUser = $dbUser;
        $this -> dbPassword = $dbPassword;
        $this -> con = $this -> connectToDB($ip, $dbUser, $dbPassword, $dbName);
    }

    function connectToDB($ip, $dbUser, $dbPassword, $dbName) {
        $con = $this -> connect($ip, $dbUser, $dbPassword);
        if ($con) {
            // Select the database name on the database server
            if ($this -> selectDatabase($dbName, $con)) {
                return $con;
            } else {
                trigger_error('Can\'t use ' . $dbName . ' : ' . mysql_error($con), E_USER_ERROR);
            }
        } else {
            trigger_error("Could not connect to " . $ip . ": " . mysql_error($con), E_USER_ERROR);
        }
        return false;
    }

    function threadSafeWPQuery($query, $mode = "READ") {
        $this->queryWP("LOCK TABLES task $mode;");
        $results = $this->queryWP($query);
        if ($mode != "READ") {
            $this->queryWP("COMMIT;");
        }
        $this->queryWP("UNLOCK TABLES;");
        return $results;
    }

    function queryWP($query) {
        return mysql_query($query, $this -> wpCon);
    }
    
 	function queryDB($query) {
        return mysql_query($query, $this -> con);
    }
    
    function queryCon($query, $con){
    	return mysql_query($query, $con);
    }

    function __destruct() {
        $this->queryDB("UNLOCK TABLES;");
        /*
         if ($this -> wpCon) {
		$this->queryWP("UNLOCK TABLES;");
         mysql_close($this->wpCon);
         }
         if ($this -> con) {
         mysql_close($this->con);
         }
         */
    }

    function threadSafeQuery($query, $mode = "READ") {
        $this->queryDB("LOCK TABLES task $mode;");
        $results = $this->queryDB($query);
        if ($mode != "READ") {
            $this->queryDB("COMMIT;");
        }
        $this->queryDB("UNLOCK TABLES;");
        return $results;
    }

    function connect($ip, $user, $password) {
        return mysql_connect($ip, $user, $password);
    }

    function isConnected() {
        return isset($this -> con);
    }

    function selectDatabase($dbName, $con) {
        $this -> dbName = $dbName;
        return mysql_select_db($this -> dbName, $con);
    }

    function getDBConnection() {
        return $this -> con;
    }
    
    function getWPDBConnection() {
        return $this -> wpCon;
    }

    function getDBAccessURL() {
        return "mysql://" . $this -> dbUser . ":" . $this -> dbPassword . "@" . $this -> ip . "/" . $this -> dbName;
    }

    function getMysqliDBConnection() {
        if (!isset($this -> mysqliCon)) {
            $this -> mysqliCon = mysqli_connect($this -> ip, $this -> dbUser, $this -> dbPassword, $this -> dbName);
            if (!$this -> mysqliCon) {
                trigger_error('Could not connect to ' . $this -> ip . ': ' . mysqli_connect_error(), E_USER_ERROR);
                // Stop
            }
        }
        return $this -> mysqliCon;
    }

}
?>