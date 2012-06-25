<?php
require_once 'DBConnection.php';
class UserAccount {
	public $userID;
	public $userName;
	public $password;
	function __construct($userID, DBConnection $DBConnection) {
		$query = "Select * FROM users WHERE id=$userID";
		$results = mysql_query ( $query, $DBConnection->getDBConnection () );
		if (mysql_errno ()) {
			trigger_error ( 'Mysql Error (' . mysql_errno () . '): ' . mysql_error () );
		} else if (is_resource ( $results )) {
			$row = mysql_fetch_array ( $results );
			$this->userID = $row ["id"];
			$this->userName = $row ["user_id"];
			$this->password = $row ["user_password"];
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
	function getPassword($accountName) {
		return $this->password;
	}
}
?>