<?php
require_once 'ConfigParser.php';
require_once 'DBConnection.php';
new ConfigParser ( ); // Get the configuation details and store them as environment variables
if (isset ( $_REQUEST ['userName'] ) && isset ( $_REQUEST ['password'] )) {
	$DBConnection = new DBConnection ( mysqlServerIP2, dbname, dbuser, dbpassword );
	// Check to see if userName Already exist
	$results = $DBConnection->queryDB ( "Select id from users Where username='" . $_REQUEST ['userName'] . "'" );
	if ($results->num_rows > 0) {
		// Username already exists
		echo("Username already exists");
	} else {
		$DBConnection->queryDB ( "Insert into users (username,password) VALUES ('" . $_REQUEST ['userName'] . "','" . $_REQUEST ['password'] . "')" );
		if ($DBConnection->getDBConnection()->errno) {
			echo("Error creating account for user");
		}else{
			echo("success");
		}
	}
}else{
	echo("usernam and password required");
}

?>