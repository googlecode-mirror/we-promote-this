<?php
require_once 'ConfigParser.php';
require_once 'DBConnection.php';
new ConfigParser ( ); // Get the configuation details and store them as environment variables
if (isset ( $_REQUEST ['userName'] ) && isset ( $_REQUEST ['password'] )) {
	$DBConnection = new DBConnection ( mysqlServerIP2, dbname, dbuser, dbpassword );
	// Check to see if userName Already exist
	$results = mysql_query ( "Select id from users Where username='" . $_REQUEST ['userName'] . "'" );
	if (mysql_num_rows ( $results ) > 0) {
		// Username already exists
		echo("Username already exists");
	} else {
		mysql_query ( "Insert into users (username,password) VALUES ('" . $_REQUEST ['userName'] . "','" . $_REQUEST ['password'] . "')" );
		if (mysql_errno ()) {
			echo("Error creating account for user");
		}else{
			echo("success");
		}
	}
}else{
	echo("usernam and password required");
}

?>