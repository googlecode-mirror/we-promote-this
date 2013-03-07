<?php
require_once 'ConfigParser.php';
require_once 'DBConnection.php';
require_once 'UserAccount.php';

new ConfigParser ( ); // Get the configuation details and store them as environment variables
if (isset ( $_REQUEST ['userName'] ) && isset ( $_REQUEST ['password'] )) {
	$DBConnection = new DBConnection ( mysqlServerIP2, dbname, dbuser, dbpassword );
	$results = $DBConnection->queryDB ( "Select id from users Where username='" . $_REQUEST ['userName'] . "' AND password='" . $_REQUEST ['password'] . "'" );
	$row = $results-> fetch_assoc();
	$userID = $row ["id"];
	$userAccount = new UserAccount ( $userID, $DBConnection );
	$results = $DBConnection->queryDB ( "Select location from uploadsites order by location" );
	while ( ($row = $results-> fetch_assoc()) ) {
		$accountName = $row ['location'];
		if (isset ( $_REQUEST [$accountName . '-userName'] )) {
			$accountUserName = $_REQUEST [$accountName . '-userName'];
			$accountPassword = $_REQUEST [$accountName . '-password'];
		} else {
			$alteredAccountName = str_replace ( ".", "_", $accountName );
			//echo("Altered Account: $alteredAccountName\n");
			$accountUserName = $_REQUEST [$alteredAccountName . '-userName'];
			$accountPassword = $_REQUEST [$alteredAccountName . '-password'];
		}
		//echo ("Updating $accountName with Username: " . $accountUserName . " Password: " . $accountPassword . "\n");
		$userAccount->updateAccount ( $accountName, $accountUserName, $accountPassword );
	}
	$success = $userAccount->saveAccounts ();
	if ($success) {
		echo ("Info Has Been Saved");
	} else {
		echo ("Error Saving Info");
	}
}
?>
