<?php
require_once 'DBConnection.php';
class UserAccount {
	public $userID;
	public $userName;
	public $password;
	function __construct($userID, DBConnection $DBConnection) {
		$query = "Select * FROM users WHERE id=$userID";
		$results = $DBConnection->queryDB ( $query );
		if ($this->getDBConnection()->getDBConnection()->errno) {
			trigger_error ( 'Mysql Error (' . $this->getDBConnection()->getDBConnection()->errno . '): ' . $this->getDBConnection()->getDBConnection()->error );
		} else if (is_resource ( $results )) {
			$row = $results-> fetch_assoc();
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