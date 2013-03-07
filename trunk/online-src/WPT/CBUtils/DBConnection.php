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

    /*?
     * Function: DBConnection
     * This is the constructor for DBConnection.
     * It uses the config file on the server and stores the database values to local variables
     */
    function __construct($ip, $dbName, $dbUser, $dbPassword, $wpip = null, $wpdbname = null, $wpdbuser = null, $wpdbpassword = null) {
        if (isset($wpip) && isset($wpdbname) && isset($wpdbuser) && isset($wpdbpassword)) {
            $this -> wpCon = $this -> connectToDB($wpip, $wpdbuser, $wpdbpassword, $wpdbname);
            echo("WordPRess Connection made<br><br><br>");
        }
        $this -> ip = $ip;
        $this -> dbUser = $dbUser;
        $this -> dbPassword = $dbPassword;
        $this -> con = $this -> connectToDB($ip, $dbUser, $dbPassword, $dbName);
    }

    function connectToDB($ip, $dbUser, $dbPassword, $dbName) {
        $con = $this -> connect($ip, $dbUser, $dbPassword, $dbName);
        if ($con -> connect_errno) {
            trigger_error('Connection failed to ip: ' . $ip . ' | Database: ' . $dbName . ' : ' . $con -> connect_errno, E_CORE_ERROR);
            return false;
        } else {
            return $con;
        }
    }

    function queryWP($query) {
        return $this -> wpCon -> query($query);
    }

    function queryDB($query) {
        return $this -> con -> query($query);
    }

    function queryCon($query, $con) {
        return $con -> query($query);
    }

    function __destruct() {
        if ($this -> wpCon) {
            $this -> wpCon -> close();
        }
        if ($this -> con) {
            $this -> con -> close();
        }
    }

    function connect($ip, $user, $password, $database, $database_port = 3306) {
        return new mysqli($ip, $user, $password, $database, $database_port);
    }

    function isConnected($con) {
        return isset($con) && ($con !== false);
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

}
?>