<?php

class Name {
	
	public $firstName;
	public $lastName;
	public $dbConnect;
	
	function __construct($db) {
		$this->dbConnect = $db;
		$this->getRandomName ();
	}
	
	function getFirstName() {
		return $this->firstName;
	}
	
	function getLastName() {
		return $this->lastName;
	}
	
	function getRandomName() {
		$query = "Select * from names order by rand() limit 1";
		$results = mysql_query ( $query, $this->dbConnect );
		$row = mysql_fetch_assoc ( $results );
		$this->firstName = $row ['name'];
		
		$query = "Select * from names where name!='" . $this->firstName . "' order by rand() limit 1";
		$results = mysql_query ( $query, $this->dbConnect );
		$row = mysql_fetch_assoc ( $results );
		$this->lastName = $row ['name'];
	}
}

?>