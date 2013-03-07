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
    
    function getDBConnection(){
        $this->dbConnect->getDBConnection();
    }
	
	function getRandomName() {
		$query = "Select * from names order by rand() limit 1";
		$results = $this->getDBConnection()->queryDB ( $query, $this->dbConnect );
		$row = $results-> fetch_assoc();
		$this->firstName = $row ['name'];
		
		$query = "Select * from names where name!='" . $this->firstName . "' order by rand() limit 1";
		$results = $this->getDBConnection()->queryDB ( $query, $this->dbConnect );
		$row = $results-> fetch_assoc();
		$this->lastName = $row ['name'];
	}
}

?>