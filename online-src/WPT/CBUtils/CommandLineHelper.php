<?php
class CommandLineHelper {
	public $absRootPath;
	public $relRootPath;
    private $dbConnection;
	
	function __construct($dbCon) {
	    $this->dbConnection = $dbCon;
		$path = dirname ( __FILE__ );
		//$path = '/home/content/50/6934650/html/';
		$onlineMode = false;
		
		if (stripos ( $path, '/home/content/50/6934650/html/' ) !== false) {
			$onlineMode = true;
		}
		if (! defined ( 'ONLINEMODE' )) {
			define ( 'ONLINEMODE', $onlineMode );
		}
		// Find out where we are and search from there
		$this->absRootPath = realpath ( dirname ( __FILE__ ) . "/../" );
		$this->relRootPath .= "..";
		//echo ("Current Path (Abs): " . $this->absRootPath . "<br>");
	//echo ("Current Path (Rel): " . $this->relRootPath . "<br>");
	}
	
	function isOnline() {
		return ( bool ) (ONLINEMODE === true);
	}
	
	function find($dir, $pattern) {
		// escape any character in a string that might be used to trick
		// a shell command into executing arbitrary commands
		//$dir = escapeshellcmd ( $dir );
		// get a list of all matching files in the current directory
		$files = glob ( "$dir/$pattern" );
		//echo("File Count: ".count($files)." in $dir/$pattern<br>");
		// find a list of all directories in the current directory
		// directories beginning with a dot are also included
		foreach ( glob ( "$dir/{.[^.]*,*}", GLOB_BRACE | GLOB_ONLYDIR ) as $sub_dir ) {
			if (count ( $files ) > 0) {
				break;
			}
			$arr = $this->find ( $sub_dir, $pattern ); // resursive call
			$files = array_merge ( $files, $arr ); // merge array with files from subdirectory
		}
		// return all found files
		return $files;
	}
	
    function getDBConnection(){
        return $this->dbConnection;
    }
	
	function run_in_background($command, $output) {
		$results = "Could not run Command";
		if ($this->isOnline ()) {
		    
			$insertQuery = "insert ignore into task (cmd, output) values ('" . $this->getDBConnection()->getDBConnection()->real_escape_string(  $command ) . "','" . $this->getDBConnection()->getDBConnection()->real_escape_string ( $output ) . "')";
			//echo ("Insert Query " . $insertQuery . "<br>");
			$this->getDBConnection()->queryDB ( $insertQuery );
			$results = basename ( $output );
		} else {
			//echo ("<font color='red'><b>Running online mode</b></font><br>");
			$results = $this->startProcess ( $command, $output );
		}
		return $results;
		//sleep ( rand(5,10) ); // Sleep for 5 seconds so other taks don't get ran to quickly
	}
	
	function startProcess($command, $output) {
		// prepend $output with absolute path to log
		$processIDFile = $this->absRootPath . "/Logs/PID-" . $output;
		$output = $this->absRootPath . "/Logs/" . $output;
		//$sprintFormat = "%s >> %s 2>&1 & echo $! > %s";
		$sprintFormat = "%s >> %s 2>&1 &";
		// Extract php files and replace with absolute path
		$commandArray = explode ( " ", $command );
		foreach ( $commandArray as $key => $c ) {
			if (stripos ( $c, ".php" ) !== false) {
				//$foundFilesArray = $this->find ( $this->relRootPath, $c );
				$foundFilesArray = $this->find ( $this->absRootPath, $c );
				if (count ( $foundFilesArray ) > 0) {
					$useFile = realpath ( array_shift ( $foundFilesArray ) );
					//$useFile = array_shift($foundFilesArray);
					$commandArray [$key] = $useFile;
				} else {
					echo ("Cant find file $c<br>");
					return false;
				}
			}
		}
		$commandAltered = implode ( " ", $commandArray );
		if ($this->isOnline ()) {
			$commandPlus = "nohup /web/cgi-bin/php5 -q -d register_argc_argv=1 " . $commandAltered;
			//passthru ( sprintf ( $sprintFormat, $commandPlus, $output, $processIDFile ) );
			passthru ( sprintf ( $sprintFormat, $commandPlus, $output ) );
		} else {
			// Randomize output file so threading works
			$output = str_replace ( ".txt", "_" . date ( "_m_d_y_h_i_s_A_" ) . rand ( 0, 1100 ) . ".txt", $output ); // This lack of a unique name will cause threading issues
			//$commandPlus = "start \"CBTE_Process_$commandAltered\" /B /LOW /SEPARATE php-cgi.exe " . $commandAltered;
			//$commandPlus = "start \"CBTE_Process_$commandAltered\" /B /LOW /SEPARATE php " . $commandAltered;
			$commandPlus = "start \"WPT_Process_$commandAltered\" /B /LOW php " . $commandAltered;
			
			//echo ("Not online mode. CMD: $commandPlus<br>");
			//pclose ( popen ( sprintf ( $sprintFormat, $commandPlus, $output, $processIDFile ), 'r' ) );
			//echo (sprintf ( $sprintFormat, $commandPlus, $output ));
			pclose ( popen ( sprintf ( $sprintFormat, $commandPlus, $output ), 'r' ) );
		
		}
		return basename ( $output );
	}
	
	/*
	function isRunning($pid) {
		try {
			$result = shell_exec ( sprintf ( "ps %d", $pid ) );
			if (count ( preg_split ( "/\n/", $result ) ) > 2) {
				return true;
			}
		} catch ( Exception $e ) {
		}
		return false;
	}
	*/
	
	function __destruct() {
		//echo ("Destructor Starting<br>\n\r");
	}
}
?>