<?php
require_once 'DBConnection.php';
class UserAccount {
	public $userID;
	public $userName;
	public $accounts;
	function __construct($userID, DBConnection $DBConnection) {
		$query = "Select * FROM users WHERE id=$userID";
		$results = mysql_query ( $query, $DBConnection->getDBConnection () );
		if (mysql_errno ()) {
			trigger_error ( 'Mysql Error (' . mysql_errno () . '): ' . mysql_error () );
		} else if (is_resource ( $results )) {
			$row = mysql_fetch_array ( $results );
			$this->userID = $row ["id"];
			$this->userName = $row ["username"];
			$this->accounts = json_decode ( $row ["accounts"], true );
		} else {
			trigger_error ( 'No Valid Resource For UserID: ' . $userID );
		}
		//$this->accounts = json_decode ( '{"youtube":{"user":"cqytvideouploader11","password":"Youtube11"},"metacafe":{"user":"youtube11@chrisqueen.com","password":"Youtube11"},"viddler":{"user":"cdqytuploader11","password":"Youtube11"},"yahoo":{"user":"cdqytuploader","password":"Youtube11"}}', true );
	//var_dump ( $this->accounts );
	}
	function isValid() {
		return isset ( $this->userID );
	}
	function getUserName() {
		return $this->userName;
	}
	function getAccountUserName($accountName) {
		if (isset ( $this->accounts [$accountName] ) && isset ( $this->accounts [$accountName] ['user'] )) {
			return $this->accounts [$accountName] ['user'];
		} else {
			return null;
		}
	}
	function getAccountPassword($accountName) {
		if (isset ( $this->accounts [$accountName] ) && isset ( $this->accounts [$accountName] ['password'] )) {
			return $this->accounts [$accountName] ['password'];
		} else {
			return null;
		}
	
	}
	function saveAccounts() {
		$saved = false;
		mysql_query("LOCK TABLES users WRITE");
		$json = json_encode ( $this->accounts );
		$query = "UPDATE users SET accounts='$json' WHERE id=$this->userID";
		//echo ($query);
		mysql_query ( $query );
		if (mysql_errno ()) {
			$saved =  false;
		} else {
			$saved = true;
		}
		mysql_query("UNLOCK TABLES;");
		return $saved;
	}
	
	function updateAccount($accountName, $userName, $password) {
		$this->accounts [$accountName] = array ('user' => $userName, 'password' => $password );
	}
}
?>