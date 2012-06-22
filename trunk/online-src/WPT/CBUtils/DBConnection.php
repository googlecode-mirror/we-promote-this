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
	public $ip; // IP address of the database
	public $dbUser; // user name to login to database
	public $dbPassword; // password to login to database
	public $dbName; // name of the database to use
	public $mysqliCon; // The connection handle to the databasse using mysqli
	public $con;
	public $db_selected;
	public $connected;
	public $wpConnected;
	public $mysqli;
	
	/*?
	 * Function: DBConnection
	 * This is the constructor for DBConnection.
	 * It uses the config file on the server and stores the database values to local variables
	 */
	function __construct($ip, $dbName, $dbUser, $dbPassword, $newLink = false) {
		$this->ip = $ip;
		$this->dbUser = $dbUser;
		$this->dbPassword = $dbPassword;
		if (($this->connect ( $this->ip, $this->dbUser, $this->dbPassword, $newLink ))) {
			// Select the database name on the database server
			$this->db_selected = $this->selectDatabase ( $dbName );
			if ($this->db_selected) {
				$this->connected = true;
			} else {
				trigger_error ( 'Can\'t use ' . $this->dbName . ' : ' . mysql_error (), E_USER_ERROR );
			}
		} else {
			trigger_error ( "Could not connect to " . $this->ip . ": " . mysql_error (), E_USER_ERROR );
		}
	}
	
	function connectToWordpressDB($ip,$dbUser, $dbPassword, $dbName) {
		$this->mysqli = new mysqli ( $ip,$dbUser, $dbPassword, $dbName );
		
		/* check connection */
		if ($this->mysqli->connect_errno) {
			printf ( "Connect failed: %s\n", $this->mysqli->connect_error );
			exit ();
		}
		
		$this->wpConnected = true;
	}
	
	function queryWP($query) {
		return $this->mysqli->query ( $query );
	}
	
	function __destruct() {
		mysql_query ( "UNLOCK TABLES;" );
		if ($this->wpConnected == true) {
			$this->mysqli->close ();
		}
	}
	function connect($ip, $user, $password, $newLink = false) {
		$this->con = mysql_connect ( $ip, $user, $password, $newLink );
		if (! $this->con) {
			return false;
		} else {
			return true;
		}
	}
	function isConnected() {
		return ($this->connected === true);
	}
	
	function selectDatabase($dbName) {
		$this->dbName = $dbName;
		return mysql_select_db ( $this->dbName, $this->con );
	}
	
	function getDBConnection() {
		return $this->con;
	}
	function getDBAccessURL() {
		return "mysql://" . $this->dbUser . ":" . $this->dbPassword . "@" . $this->ip . "/" . $this->dbName;
	}
	function getMysqliDBConnection() {
		if (! isset ( $this->mysqliCon )) {
			$this->mysqliCon = mysqli_connect ( $this->ip, $this->dbUser, $this->dbPassword, $this->dbName );
			if (! $this->mysqliCon) {
				trigger_error ( 'Could not connect to ' . $this->ip . ': ' . mysqli_connect_error (), E_USER_ERROR ); // Stop 
			}
		}
		return $this->mysqliCon;
	}
}
?>