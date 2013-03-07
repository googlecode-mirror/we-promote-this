<?php
require_once 'CBAbstract.php';
require_once 'Proxy.php';
require_once '../Account/ProjectPaydayAccount.php';
require_once 'Name.php';

class ProjectPayDay extends CBAbstract {
	public $Proxy;
	function constructClass() {
		echo ("<br><hr/>STARTING ProjectPayday. Time: " . date ( "m-d-y h:i:s A" ) . "<br>");
		$this->Proxy = new Proxy ( );
		
		$this->createAccount (  ); // Creates account for yourself first
		
		// for each project payday User create a new account for them
		$query = "Select * from projectpaydaymembers";
		$results = $this->getDBConnection()->queryDB ( $query );
		while ( ($ros = $results-> fetch_assoc()) ) {
			$id = $ros ['id'];
			$this->createAccount ( $id );
		}
	
	}
	
	function createAccount($id = null) {
		$proxy = $this->Proxy->getRandomProxy ();
		if(isset($id)){
		$ppAccount = new ProjectPaydayAccount ( $proxy ['proxy'], $proxy ['port'], $id );
		}else{
			$ppAccount = new ProjectPaydayAccount ( $proxy ['proxy'], $proxy ['port']);
		}
		
		$name = new Name ( );
		$email = substr ( $name->firstName, 0, 1 ) . $name->lastName . rand ( 900 ) . "@chrisqueen.com";
		$success = $ppAccount->create ( $name->firstName, $name->lastName, $email, "mypass123" );
		if ($success) {
			echo ("ProjectPayday Account Was Created");
		} else {
			echo ("ProjectPayday Account Was NOT Created");
		}
	}

}

$obj = new ProjectPayDay ( );
?>