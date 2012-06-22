<?php
$dir = $_REQUEST ['dir'];
function getDirFileCount($directory) {
	if (glob ( "$directory*.*" ) != false) {
		$filecount = count ( glob ( "$directory*.*" ) );
	} else {
		$filecount = 0;
	}
	return $filecount;
}
echo getDirFileCount ( "../../".$dir );
?>