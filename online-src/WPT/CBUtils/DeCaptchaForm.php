<?php
error_reporting ( 0 );

$captchaFile = $_REQUEST ['captcha'];
//

if (isset ( $captchaFile )) {
	//$captchaFile = urldecode($captchaFile);
	//echo("Captcha File: $captchaFile<br>");
	require_once 'DeCaptcha.php';
	$results = '';
	$deCaptcha = new DeCaptcha ( 'frostbyte07', 'Neeuq011$' );
	$balance = $deCaptcha->getBalance ();
	$results .= "Balance: $balance ";
	if ($balance > 0) {
		$results = $deCaptcha->getCatchaText ( $captchaFile );
		if(strlen($results)<0){
			$results = "Error: Couldn't not solve captcha";
			$deCaptcha->reportLastCatchaIncorrect();
		}
		
	} else {
		$results = "Error: No Balance Left To Solve Captcha";
	}
	echo $results;
} else {
	echo ('<html><body>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.1/jquery.min.js" type="text/javascript"></script>
	<script type="text/javascript">
	function solveCaptcha(){
	$.ajax( {
		type :"GET",
		url :"DeCaptchaForm.php",
		data : "captcha="+document.captchaForm.captcha.value,
		dataType :"html",
		async: false,
        cache: false,
        timeout: 30000,
		success : function(res) {
		document.captchaForm.captchaText.value = res;
		document.captchaForm.captchaImage.src = document.captchaForm.captcha.value;
		},
		error: function(jqXHR, textStatus, errorThrown){
		alert("Error: "+textStatus);
		}
	});
	
	
	}
	</script>
	<center>
	<form name="captchaForm" action="DeCaptchaForm.php" >Captcha URL: <input type="text" name="captcha"><input type="button" value="Sovle" onClick="solveCaptcha();"><br>
	Answer: <input type="text" name="captchaText" value=""><br>
	<img src="" name="captchaImage">
	</form></center></body></html>');
}

?>