<?php

class Upload {
	
	function __construct() {
		$pid = $_REQUEST ['pid'];
		$dirSuffix = '/CBCreatedVideos/' . $pid . "/";
		//$dirSuffix = '/CBCreatedVideo/' . $pid . "/";
		$uploaddir = dirname ( __FILE__ ) . $dirSuffix;
		if (! file_exists ( $uploaddir )) {
			mkdir ( $uploaddir, 0777, true );
		}
		
		$dirCount = $this->getDirFileCount ( $uploaddir );
		
		foreach ( $_FILES as $file ) {
			$tmpFile = $file ['tmp_name'];
			$tmpFileOriginalName = $file ['name'];
			//echo ("Tmp File: $tmpFileOriginalName<br>");
			$path_parts = pathinfo ( $tmpFileOriginalName );
			$ext = $path_parts ['extension'];
			
			// Change file name based on previous files in folder
			$uploadFileName = $pid . "-$dirCount." . $ext;
			$uploadFileLocation = $uploaddir . $uploadFileName;
			
			if (move_uploaded_file ( $tmpFile, $uploadFileLocation )) {
				$url = "http://chrisqueen.com/ClickBank-Traffic-Explosion/CB/Video" . $dirSuffix . $uploadFileName;
				echo "File is valid, and was successfully uploaded <a href='$url'>HERE</a>.\n";
			} else {
				echo "File (" . $tmpFileOriginalName . ") Upload Failed. Possible file upload attack!\n";
			}
		}
	
		//echo 'Here is some more debugging info for pid :' . $pid . "</pre><br>\n\r<pre>";
	//print_r ( $_FILES );
	}
	
	function getDirFileCount($directory) {
		if (glob ( "$directory*.*" ) != false) {
			$filecount = count ( glob ( "$directory*.*" ) );
		} else {
			$filecount = 0;
		}
		return $filecount;
	}

}

if (isset ( $_REQUEST ['pid'] ) && isset ( $_REQUEST ['key'] ) && strcmp ( $_REQUEST ['key'], "CBTE" ) === 0) {
	$upload = new Upload ();
} else {
	//<input type="hidden" name="MAX_FILE_SIZE" value="100000" />
	echo ('<form enctype="multipart/form-data" action="Upload.php" method="POST">
Choose a file to upload: <input name="file" type="file" /><br />
<input type="hidden" name="pid" value="test" />
<input type="hidden" name="key" value="CBTE" />
<input type="submit" value="Upload File" />
</form>');
}
?>