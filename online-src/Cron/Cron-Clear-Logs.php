<?php
require_once 'CronAbstract.php';

class CronClearLogs extends CronAbstract {
	function runCron() {
		$path = realpath ( dirname ( __FILE__ ) . "/../WPT/Logs/" );
		// If today is monday clear logs
		$dayOfWeek = ( int ) date ( "w" );
		if ($dayOfWeek == 0) {
			$this->resetFilesIn ( $path );
		}
	}
	
	function resetFilesIn($path) {
		if (($dh = opendir ( $path ))) {
			while ( false !== ($dat = readdir ( $dh )) ) {
				if ($dat != "." && $dat != ".." && $dat != ".svn" && $dat != "tmp") {
					$path_parts = pathinfo ( $dat );
					$ext = $path_parts ['extension'];
					if ($ext == "txt") {
						$filePath = $path . DIRECTORY_SEPARATOR . $dat;
						//echo ("Ext found: $ext for $filePath<br>");
						// Clean file
						$fh = fopen ( $filePath, 'w' );
						fclose ( $fh );
					}
				}
			}
			closedir ( $dh );
		}
	}
}
new CronClearLogs ( );
?>