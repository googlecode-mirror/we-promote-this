<?php
session_start ();

require_once 'CBAbstract.php';
require_once '../Account/TwitterAccount.php';
require_once 'Proxy.php';

class FiverrAccountCreator extends CBAbstract {
	
	public $username;
	public $Proxy;
	
	function __construct($argv) {
		parent::__construct ();
		$this->handleARGV ( $argv );
	}
	function constructClass() {
		$this->Proxy = new Proxy();
	}
	function handleARGV($argv) {
		// Handle Account Credits
		if (isset ( $argv ['paypal'] ) && isset ( $argv ['tid'] ) && isset ( $argv ['credits'] ) && isset ( $argv ['paypalemail'] )) {
			$this->getLogger ()->logInfo ( "Payment Amount: " . $argv ['paymentAmount'] );
			$this->addCredits ( $argv ['tid'], $argv ['credits'], $argv ['paypalemail'] );
		}
		if (! isset ( $_SESSION ['username'] ) || ! isset ( $_SESSION ['password'] )) {
			echo ("You are not logged in");
		} else {
			$this->username = $_SESSION ['username'];
			if (isset ( $argv ['create'] )) {
				$this->createAccounts ( $argv );
			} else if (isset ( $argv ['excel'] )) {
				$this->getAccountsAsExcel ();
			
			}
		}
	}
	
	function addCredits($tid, $credits, $useremail) {
		// check if $tid has already been processed
		$query = "select tid from paypal where tid='$tid'";
		$results = mysql_query ( $query );
		$count = mysql_num_rows ( $results );
		if ($count > 0) {
			// TID already handled
		} else {
			$totalCredits = $this->getUserCreditsByEmail ( $useremail ) + $credits;
			$updateQuery = "Update members set credits=$totalCredits where email='$useremail'";
			mysql_query ( $updateQuery );
		}
	}
	
	function getAccountsAsExcel() {
		$output = "Account Type,User Name, Password, Email\n";
		$accounts = $this->getUserAccounts ();
		foreach ( $accounts as $typeName => $accountType ) {
			foreach ( $accountType as $account ) {
				$output .= $typeName . "," . $account ['username'] . "," . $account ['password'] . "," . $account ['email'] . "\n";
			}
		}
		header ( "Content-disposition: attachment; filename=" . $this->username . "_accounts.csv" );
		header ( "Content-Type: application/force-download" );
		header ( "Content-Transfer-Encoding: binary" );
		header ( "Content-Length: " . strlen ( $output ) );
		header ( "Pragma: no-cache" );
		header ( "Expires: 0" );
		echo ($output);
	}
	
	
	
	function getUserCredits($username = null) {
		if (! isset ( $username )) {
			$username = $this->username;
		}
		$query = "SELECT credits FROM members where username='" . $username . "'";
		$results = mysql_query ( $query );
		$row = mysql_fetch_assoc ( $results );
		return $row ['credits'];
	}
	
	function getUserCreditsByEmail($email) {
		$query = "SELECT credits FROM members where email='" . $email . "'";
		$results = mysql_query ( $query );
		$row = mysql_fetch_assoc ( $results );
		return $row ['credits'];
	}
	
	function getUserAccounts() {
		$query = "SELECT accounts FROM members where username='" . $this->username . "'";
		$results = mysql_query ( $query );
		$row = mysql_fetch_assoc ( $results );
		if (isset ( $row ['accounts'] )) {
			return json_decode ( $row ['accounts'], true );
		} else {
			return array ();
		}
	}
	
	function updateUserAccounts(array $accounts) {
		$json = json_encode ( $accounts );
		$query = "UPDATE members SET accounts='$json' where username='" . $this->username . "'";
		mysql_query ( $query );
	}
	
	function addToAccounts(&$accounts, Account $account, $serviceName) {
		if (! isset ( $accounts [$serviceName] )) {
			$accounts [$serviceName] = array ();
		}
		$accounts [$serviceName] [] = array ('username' => $account->userName, 'password' => $account->password, 'email' => $account->email );
	}
	
	function createAccounts($argv) {
		echo ("Creating Accounts<br>");
		// Tally up total account user wants to create
		$total = 0;
		foreach ( $argv as $argIndex => $arg ) {
			if (stripos ( $argIndex, "_number" ) !== false) {
				$total += $arg;
			}
		}
		$userCredits = $this->getUserCredits ();
		if ($total > $userCredits) {
			echo ("You are attempting to create " . $total . " accounts but you only have " . $userCredits . " credits.<br>Add More Credits To Your Account.");
		} else {
			$accounts = $this->getUserAccounts ();
			if (isset ( $argv ['twitter_number'] )) {
				while ( $argv ['twitter_number'] > 0 ) {
					$proxy = $this->Proxy->getRandomProxy();
					$obj = new TwitterAccount ( $proxy ['proxy'], $proxy ['port'] );
					$range = 15 - strlen ( $this->username );
					$range = ($range < 15) ? $range : 15;
					$max = pow ( 10, $range );
					$tUsername = $this->username . rand ( 0, $max );
					$success = $obj->create ( $this->username, $tUsername, 'mypassword', $tUsername . '@chrisqueen.com' );
					if ($success) {
						echo ("Twitter Account Created. Username:  " . $obj->userName . "<br>");
						$argv ['twitter_number'] --;
						$userCredits --;
						$this->addToAccounts ( $accounts, $obj, 'twitter' );
						mysql_query ( "Update members set credits=" . $userCredits . " where username='" . $this->username . "'" );
					} else {
						echo ("Twitter Account Was NOT Created");
					}
				
				}
			}
			$this->updateUserAccounts ( $accounts );
		}
	}
	
	function displayCreationForm() {
		echo ('
<form name="accountsNeeded" action="FiverrAccountCreator.php" method="Post">
<table>
<tr>
<td>
<image src="http://ping.fm/_images/icons/twitter.png" alt="Twitter"> Twitter
</td><td> 
<input type="text" name="twitter_number" value="0" style="width:50px;height:23px;font-weight:bold;" />
</td>
</tr>

<tr colspan=2>
<td align="center">
<input type="submit" value="Create">
</td>
</tr>
</table>
<input type="hidden" name="create" value="1">
</form>
');
	}

}

$obj = new FiverrAccountCreator ( $_REQUEST );

?>