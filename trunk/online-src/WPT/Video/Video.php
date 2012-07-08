<?php
class Video {
	public $path;
	public $slug;
	public $pid;
	public $title;
	public $description;
	public $keywords;
	public $link;
	public $hop;
	function __construct($pid, $title, $description, $keywords, $userWPID = "1", $rootPath = null) {
		$this->pid = $pid;
		if (isset ( $rootPath )) {
			$path = $rootPath . $this->pid . "/";
		} else {
			$path = dirname ( __FILE__ ) . "/WPTCreatedVideos/" . $this->pid . "/";
		}
		//echo("Video Root Path: $path\n");
		$tmpPath = $this->grabRandomVideo ( $path );
		if ($tmpPath != false) {
			$this->path = realpath ( $tmpPath );
		}
		$this->title = $this->removeForbiddenChars ( htmlspecialchars_decode ( $title ) );
		/*** make sure there is an http:// on all URLs ***/
		$description = preg_replace ( "/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i", "$1http://$2", $description );
		// Remove all links from description
		$description = preg_replace ( '/http[^\s]+/', "", $description );
		$this->description = htmlspecialchars_decode ( $this->removeForbiddenChars ( $description ) );
		if (is_array ( $keywords )) {
			$this->keywords = $keywords;
		} else {
			$this->keywords = explode ( ",", $keywords );
		}
		if (! isset ( $userWPID ) || strlen ( $userWPID ) == 0) {
			$userWPID = 1;
		}
		$this->link = "http://www.WePromoteThis.com/WPT/" . $this->pid . "/" . $userWPID;
		$this->hop = "http://www.WePromoteThis.com/hop/"  . $userWPID;
	}
	function removeForbiddenChars($txt) {
		$txt = str_replace ( array ('<sup>&reg;</sup>', '<sup>&copy;</sup>', '<sup>TM</sup>', '&reg;', '&#0153;', '&#0169;', '&#0174;' ), '', $txt );
		$forbidden = array (174, 194, 132, 162, 226 );
		$newstring = '';
		for($i = 0; $i < strlen ( $txt ); $i ++) {
			//echo($txt [$i]."=".ord ( $txt [$i] )."<br>");
			if (in_array ( ord ( $txt [$i] ), $forbidden ))
				continue;
			else
				$newstring .= $txt [$i];
		}
		return $newstring;
	}
	
	function __toString() {
		return $this->toString ();
	}
	
	function toString() {
		$classVars = get_class_vars ( get_class ( $this ) );
		$vars = '';
		foreach ( $classVars as $name => $value ) {
			$value = $this->$name;
			if (is_array ( $value )) {
				$value = implode ( ",", $value );
			}
			$vars .= "<b>$name</b> : $value\n<br>";
		}
		return $vars;
	}
	function keywordsAsArray() {
		return $this->keywords;
	}
	function keywordsAsString($delimiter = ",") {
		return implode ( $delimiter, $this->keywords );
	}
	function isValid() {
		return (isset ( $this->pid ) && isset ( $this->path ) && is_file ( $this->path ));
	}
	function grabRandomVideo($rootPath) {
		$rootPath = realpath ( $rootPath ) . "/";
		//echo('working path: '.getcwd()."\n path: $path\n");
		//$this->getLogger()->logInfo ("Video Path: $path<br>");
		$videoArray = array ();
		if (($dh = opendir ( $rootPath ))) {
			while ( false !== ($dat = readdir ( $dh )) ) {
				if ($dat != "." && $dat != ".." && $dat != ".svn" && stripos ( $dat, ".mp4" ) !== false) {
					$videoArray [] = $dat;
				}
			}
			closedir ( $dh );
			//Get random video
			$index = rand ( 0, count ( $videoArray ) - 1 );
			$this->slug = $videoArray [$index];
			$path = $rootPath . $this->slug;
			return $path;
		} else {
			return false;
		}
	}
}
?>