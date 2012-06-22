<?php
require_once 'DBConnection.php';
class OnlyWireAccount {
	public $id;
	public $email;
	public $activeServices;
	public $accounts;
	public $deleteKey;
	public $currentMonth;
	public $currentDay;
	public $monthlySubmissionLimit = 2350;
	private $dbConnection;
	function __construct($id, DBConnection $DBConnection) {
		$this->currentMonth = date ( "n" );
		$this->currentDay = date ( "j" );
		$this->id = $id;
		$this->dbConnection = $DBConnection;
		$this->updateFromDB ();
		$this->remove2MonthOldSubmissions ();
	}
	function updateFromDB() {
		$query = "Select * FROM onlywire WHERE id=$this->id";
		mysql_query ( "LOCK TABLES onlywire WRITE" );
		$results = mysql_query ( $query, $this->dbConnection->getDBConnection () );
		if (mysql_errno ()) {
			trigger_error ( 'Mysql Error (' . mysql_errno () . '): ' . mysql_error () );
		} else if (is_resource ( $results )) {
			$row = mysql_fetch_array ( $results );
			$this->id = $row ["id"];
			$this->email = $row ["email"];
			$this->activeServices = $row ["activeservices"];
			$this->accounts = json_decode ( $row ["accounts"], true );
			$this->deleteKey = $row ["deletekey"];
		} else {
			trigger_error ( 'No Valid Resource For id: ' . $this->id );
		}
		mysql_query ( "UNLOCK TABLES;" );
	}
	
	function isValid() {
		return isset ( $this->email );
	}
	function getActiveServices() {
		$this->updateFromDB ();
		return $this->activeServices;
	}
	function setActiveServices($count) {
		if (is_numeric ( $count )) {
			$this->activeServices = $count;
			$this->saveChanges ();
		}
	}
	function getDeleteKey() {
		return $this->deleteKey;
	}
	function getNumberOfAccount() {
		$this->updateFromDB ();
		return count ( $this->accounts );
	}
	function getRandAccountIndex() {
		$index = - 1;
		while ( ! $this->canSubmit ( $index ) ) {
			$index = array_rand ( $this->accounts );
		}
		return $index;
	}
	function removeAccount($accountIndex) {
		if (isset ( $this->accounts [$accountIndex] )) {
			unset ( $this->accounts [$accountIndex] );
		}
		$this->saveChanges ();
	}
	function getAccountIndexByUserName($username) {
		$index = - 1;
		for($i = 0; $i < $this->getNumberOfAccount (); $i ++) {
			if (isset ( $this->accounts [$i] ['user'] ) && strcasecmp ( $this->accounts [$i] ['user'], $username ) !== false) {
				$index = $i;
				break;
			}
		}
		return $index;
	}
	
	function getAccountUserName($accountIndex) {
		if (isset ( $this->accounts [$accountIndex] ) && isset ( $this->accounts [$accountIndex] ['user'] )) {
			return $this->accounts [$accountIndex] ['user'];
		} else {
			return null;
		}
	}
	function getAccountPassword($accountIndex) {
		if (isset ( $this->accounts [$accountIndex] ) && isset ( $this->accounts [$accountIndex] ['password'] )) {
			return $this->accounts [$accountIndex] ['password'];
		} else {
			return null;
		}
	}
	function setAccountPassword($accountIndex, $password) {
		if (isset ( $this->accounts [$accountIndex] )) {
			$this->accounts [$accountIndex] ['password'] = $password;
		}
		$this->saveChanges ();
	}
	function getSubmissionsThisMonth($accountIndex) {
		$this->updateFromDB ();
		if (isset ( $this->accounts [$accountIndex] ) && isset ( $this->accounts [$accountIndex] ['submissions'] ) && isset ( $this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] )) {
			$count = 0;
			foreach ( $this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] as $daySubmits ) {
				$count += $daySubmits;
			}
			return $count;
		} else {
			return 0;
		}
	}
	
	function getSubmissionsToday($accountIndex) {
		$this->updateFromDB ();
		if (isset ( $this->accounts [$accountIndex] ) && isset ( $this->accounts [$accountIndex] ['submissions'] ) && isset ( $this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] ) && isset ( $this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] [$this->currentDay] )) {
			return $this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] [$this->currentDay];
		} else {
			return 0;
		}
	}
	private function setSubmissionsToday($accountIndex, $submissionCount) {
		if (isset ( $this->accounts [$accountIndex] )) {
			if (! isset ( $this->accounts [$accountIndex] ['submissions'] )) {
				$this->accounts [$accountIndex] ['submissions'] = array ();
			}
			if (! isset ( $this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] )) {
				$this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] = array ();
			}
			$this->accounts [$accountIndex] ['submissions'] [$this->currentMonth] [$this->currentDay] = $submissionCount;
			return true;
		} else {
			return false;
		}
	}
	function addSubmission($accountIndex) {
		$added = false;
		if (isset ( $this->accounts [$accountIndex] )) {
			$this->updateFromDB ();
			$subMissionsToday = $this->getSubmissionsToday ( $accountIndex ) + $this->getActiveServices();
			$added = $this->setSubmissionsToday ( $accountIndex, $subMissionsToday );
			$this->saveChanges ();
		}
		return $added;
	}
	function canSubmit($accountIndex) {
		$submissionsLeft = $this->monthlySubmissionLimit - $this->getSubmissionsThisMonth ( $accountIndex );
		$daysLeft = date ( "t" ) - $this->currentDay; // days in this month minus todays date
		$dailySubmissionLimit = floor ( $submissionsLeft / $daysLeft ); // max submissions left in this month divided by days left in this month
		return (isset ( $this->accounts [$accountIndex] )) && ($this->getSubmissionsThisMonth ( $accountIndex ) < $this->monthlySubmissionLimit) && ($this->getSubmissionsToday ( $accountIndex ) < $dailySubmissionLimit);
	}
	function isUsableToday() {
		$usable = false;
		for($i = 0; $i < $this->getNumberOfAccount (); $i ++) {
			if ($this->canSubmit ( $i )) {
				$usable = true;
				break;
			}
		}
		return $usable;
	}
	
	function remove2MonthOldSubmissions() {
		for($i = 0; $i < $this->getNumberOfAccount (); $i ++) {
			$previousMonth = date ( 'n', strtotime ( 'now - 2 months' ) );
			if (isset ( $this->accounts [$i] ) && isset ( $this->accounts [$i] ['submissions'] ) && isset ( $this->accounts [$i] ['submissions'] [$previousMonth] )) {
				unset ( $this->accounts [$i] ['submissions'] [$previousMonth] );
			}
		}
		$this->saveChanges ();
	}
	function saveChanges() {
		$saved = false;
		mysql_query ( "LOCK TABLES onlywire WRITE" );
		$json = json_encode ( $this->accounts );
		$query = "UPDATE onlywire SET accounts='$json', activeservices=" . $this->activeServices . " WHERE id=$this->id";
		//echo ($query . "<br>");
		while ( $saved === false ) {
			mysql_query ( $query );
			if (mysql_errno ()) {
				$saved = false;
				sleep(5); //
			} else {
				$saved = true;
			}
		}
		mysql_query ( "UNLOCK TABLES;" );
		return $saved;
	}
	function addAccount($userName, $password) {
		$this->accounts [] = array ('user' => $userName, 'password' => $password );
		$this->saveChanges ();
	}
}
?>