<?php
echo ("<html><body>");
$path =  realpath(dirname ( __FILE__ ) . "/../Logs/") ;
//$path =  "../Logs/" ;
if (isset ( $_REQUEST ['log'] )) {
	$userFile = $_REQUEST ['log'];
	// Strip userFile of any directories.
	

	$tmpDir = explode ( "/", $userFile );
	$userFile = $tmpDir [count ( $tmpDir ) - 1];
	//$location = "http://" . RemoteHost . RemoteHostRootFolder . LogLocation . $userFile;
	//$location = $_SERVER['DOCUMENT_ROOT']."/".RemoteHostRootFolder . LogLocation . $userFile;
	$location = $path . "/" . $userFile;
	//echo("Location: $location<br>");
	if (file_exists ( $location )) {
		echo (file_get_contents ( $location ));
	} else {
		echo ("Can't Find File $userFile<br> Redirecting in 5 seconds...");
		$url = (! empty ( $_SERVER ['HTTPS'] )) ? "https://" . $_SERVER ['SERVER_NAME'] . ":" . $_SERVER ['SERVER_PORT'] . $_SERVER ['REQUEST_URI'] : "http://" . $_SERVER ['SERVER_NAME'] . ":" . $_SERVER ['SERVER_PORT'] . $_SERVER ['REQUEST_URI'];
		//echo ("<br>URL 1st: " . $url);
		$url_parts = @parse_url ( $url );
		$url = $url_parts ['scheme'] . "://" . $url_parts ['host'] .":". $url_parts ['port'] . $url_parts ['path'];
		//echo ("<br>URL: " . $url);
		echo ("<script type='text/JavaScript'>setTimeout(\"location.href = '" . $url . "';\",5000);</script>");
	}
} else {
	//echo("<br>Path: $path<br>");
	if ($dh = opendir ( $path  )) {
		while ( false !== ($dat = readdir ( $dh )) ) {
			if ($dat != "." && $dat != ".." && $dat != ".svn") {
				echo ("<a href='ShowLog.php?log=" . $dat . "'>" . $dat . "</a><br>");
			}
		}
		closedir ( $dh );
	}
}

echo ("</body></html>");
?>