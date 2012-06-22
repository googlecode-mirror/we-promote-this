<?php
error_reporting ( E_ALL ^ E_NOTICE ); // turn on all errors, warnings minus notices
require_once 'ConfigParser.php';
require_once 'UserAccount.php';
require_once 'DBConnection.php';
new ConfigParser ( ); // Get the configuation details and store them as environment variables
$output = "<html><body>
<script src='https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js' type='text/javascript'></script>";

function getLoginForm() {
	$output = "
	<script type='text/javascript'>
	function createUser()
	{
	$.ajax( {
		type :'GET',
		url :'UserAccountCreate.php',
		data : 'userName='+document.login.userName.value+'&password='+document.login.password.value,
		dataType :'html',
		async: false,
        cache: false,
        timeout: 30000,
		success : function(res) {
		if(res=='success'){
		document.login.submit();
		}else {
		alert(res);
		}},
		error: function(jqXHR, textStatus, errorThrown){
		alert('Error: '+textStatus);
		}
	});
	}
	</script>";
	$output .= "<form name='login' action='UserAccountLogin.php'>User Name: <input type='text' name='userName'> Password: <input type='password' name='password'><br><input type='submit' value='Login'><input type='button' value='Create' onClick='createUser();'></form>";
	return $output;
}

if (isset ( $_REQUEST ['userName'] ) && isset ( $_REQUEST ['password'] )) {
	$DBConnection = new DBConnection ( mysqlServerIP2, dbname, dbuser, dbpassword );
	$results = mysql_query ( "Select id from users Where username='" . $_REQUEST ['userName'] . "' AND password='" . $_REQUEST ['password'] . "'" );
	$row = mysql_fetch_assoc ( $results );
	$userID = $row ["id"];
	$userAccount = new UserAccount ( $userID, $DBConnection );
	if ($userAccount->isValid ()) {
		$output .= "
		<script type='text/javascript'>
		function update(){
		var formData = 'save=1';
		// store form elements
		 var elem = document.accountInfo.elements;
        for(var i = 0; i < elem.length; i++)
        {
        formData+='&'+elem[i].name+'='+elem[i].value;    
        } 
        //alert(formData);
		$.ajax( {
		type :'GET',
		url :'UserAccountUpdate.php',
		data : formData,
		dataType :'html',
		async: false,
        cache: false,
        timeout: 30000,
		success : function(res) {
		alert(res);
		},
		error: function(jqXHR, textStatus, errorThrown){
		alert('Error: '+textStatus);
		}
		});
		}
		
		function passwordChange(){
			$('input[class=\"pw\"]').each(function(index, element) {
			//$('input[type=\"password\"]').each(function(index, element) {
			//$(this).type='text';
			if(document.accountInfo.pwBox.checked)
			{
			element.type='text';
			}
			else{
			element.type='password';
			}
		});
		}
		</script>
		<form name='accountInfo'>
		<input type='hidden' name='userName' value='" . $_REQUEST ['userName'] . "'><input type='hidden' name='password' value='" . $_REQUEST ['password'] . "'>
		<table border=0 width='50%' align='center'>
		";
		$results = mysql_query ( "Select location, working, type from uploadsites order by type, location" );
		$videoTypeSwitch = false;
		$articleTypeSwitch = false;
		while ( ($row = mysql_fetch_assoc ( $results )) ) {
			$location = $row ['location'];
			$working = ( bool ) $row ['working'];
			$type = $row ['type'];
			if(strcasecmp("video",$type)===0 && $videoTypeSwitch==false){
				$output .= "<tr><th colspan='3'><h1>Video</h1></th></tr>
				<tr><th>Location</th><th>Username</th><th>Password</th>";
				$videoTypeSwitch = true;
				
			}else if(strcasecmp("article",$type)===0 && $articleTypeSwitch==false){
				$output .= "<tr><th colspan='3'><h1>Article</h1></th></tr>
				<tr><th>Location</th><th>Username</th><th>Password</th>";
				$articleTypeSwitch = true;
			}
			$accountUserName = $userAccount->getAccountUserName ( $location );
			$accountPassword = $userAccount->getAccountPassword ( $location );
			$accountUserValue = '';
			$accountPasswordValue = '';
			if (isset ( $accountUserName )) {
				$accountUserValue = "value='$accountUserName'";
			}
			if (isset ( $accountPassword )) {
				$accountPasswordValue = "value='$accountPassword'";
			}
			$disabled = "";
			if (!$working) {
				$style = "STYLE='color: #FFFFFF; background-color: #808080;'";
				$disabled = "disabled $style";
			}
			$output .= "<tr><td><b>$location</b></td><td align='center'><input type='text' $disabled name='$location-userName' $accountUserValue></td><td align='center'><input class='pw' type='text' $disabled name='$location-password' $accountPasswordValue></td></tr>";
		}
		$output .= "<tr><td colspan='3' align='center'><input type='button' value='Save' onClick='update();'><input type='checkbox'  value='' name='pwBox' onClick='passwordChange();' checked> Show Passwords</td></tr>
		</table>
		</form>";
	} else {
		$output .= "<font color='red'><b>Error Logging In</b></font><br>" . getLoginForm ();
	}

} else {
	$output .= getLoginForm ();
}
$output .= "</body></html>";
echo ($output);
?>