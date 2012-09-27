00<?php

// Get File to upload from args
define ( 'SALT', 'WePromoteThisAllDayLong313' );
$acceptedExts = array ("mp4", "html" );

function decrypt($text) {
	return trim ( mcrypt_decrypt ( MCRYPT_RIJNDAEL_256, SALT, base64_decode ( $text ), MCRYPT_MODE_ECB, mcrypt_create_iv ( mcrypt_get_iv_size ( MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB ), MCRYPT_RAND ) ) );
}

function getDirFileCount($directory) {
	if (glob ( "$directory*.*" ) != false) {
		$filecount = count ( glob ( "$directory*.*" ) );
	} else {
		$filecount = 0;
	}
	return $filecount;
}

if (isset ( $_REQUEST ["v"] )) {
	$v = $_REQUEST ["v"];
}

if (isset ( $v )) {
	$v = decrypt ( $v );
	$v = json_decode ( $v, true );
	$file = urldecode ( $v ['file'] );
	$pid = $v ['pid'];
	$referip=$_SERVER['REMOTE_ADDR'];
	$authenticFileName = $file;
	$status = false;
	// upload file to directory
	$uploaddir = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . "Video/WPTCreatedVideos/" . $pid . "/";
	$dirCount = getDirFileCount ( $uploaddir );
	$status = "";
	foreach ( $_FILES as $file ) {
		$tmpFileOriginalName = $file ['name'];
		if (strcasecmp ( $authenticFileName, $tmpFileOriginalName ) == 0) {
			$path_parts = pathinfo ( $tmpFileOriginalName );
			$ext = $path_parts ['extension'];
			
			if (array_search ( $ext, $acceptedExts ) !== false) {
				$tmpFile = $file ['tmp_name'];
				
				// Change file name based on previous files in folder
				$uploadFileName = $pid . "-$dirCount from ($referip)." . $ext;
				$uploadFileLocation = $uploaddir . $uploadFileName;
				
				if (! file_exists ( $uploaddir )) {
					mkdir ( $uploaddir, 0777, true );
				}
				
				if (move_uploaded_file ( $tmpFile, $uploadFileLocation )) {
					$status = "success";
				} else {
					$status = "File (" . $tmpFileOriginalName . ") Upload Failed. Possible file upload attack!\n";
				}
			} else {
				$status = "The file extension (" . $ext . ") is not acceptable for uploading";
			}
		} else {
			$status = "File was not uploaded by WPT uploader, Authname: $authenticFileName , Tmp file orig name: $tmpFileOriginalName";
		}
	}
	die ( $status );
}else{
	echo ("Nothing to upload.");
}

?>