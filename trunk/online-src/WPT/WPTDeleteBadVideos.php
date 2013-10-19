<?php
require_once ("CBUtils/CBAbstract.php");
class WPTDeleteBadVideos extends CBAbstract {
	//function __construct() {
		//parent::__construct();
	//}

	function __destruct() {
		parent::__destruct();
	}

	function constructClass() {
		$this -> handleARGV();
	}

	function handleARGV() {
		global $argv;
		if (!isset($argv) || count($argv) <= 1) {
			$this -> startCheckingVideos();
		}
		else {
			array_shift($argv);
			foreach ($argv as $value) {
				$keyArray = split("=", $value);
				$key = $keyArray[0];
				$keyValue = $keyArray[1];
				switch ($key) {
					case "videoid" :
						$videoId = $keyValue;
						if (isset($videoId)) {
							$this -> checkVideoId($videoId);
						}
						break;
				}
			}
		}
	}

	function startCheckingVideos() {
		$videoCheckLimit = 100;
		$query = "SELECT postURL FROM post as p INNER JOIN users as u ON u.id=p.user_id WHERE posted=1 order by lastcheck asc, posttime asc limit " . $videoCheckLimit;
		$results = $this -> runQuery($query, $this -> getDBConnection() -> getDBConnection());
		$class = get_class($this);
		$file = $class . ".txt";
		while (($row = $results -> fetch_assoc())) {
			$postURL = $row["postURL"];
			$postURLParts = explode('watch?v=', $postURL);
			$postURLParts = array_pop($postURLParts);
			$postURLParts = explode('&', $postURLParts);
			$videoId = array_shift($postURLParts);
			$cmd = $class . ".php videoid=$videoId";
			//echo ("\n\nCMD: $cmd\n");
			$this -> getCommandLineHelper() -> run_in_background($cmd, $file);
		}

	}

	function checkVideoId($videoId) {
		// See if video is valid
		$api = 'http://gdata.youtube.com/feeds/api/videos/';
		$headers = get_headers($api . $videoId, true);
		if ($headers[0] == "HTTP/1.0 200 OK") {
			//echo("<font color='green'>Youtube Video ID: <a href='".$api.$videoId."'>" . $videoId . '</a> IS Valid!!!</font><br>');
			$updateQuery = "Update post SET lastcheck=now() WHERE postURL like '%watch?v=" . $videoId . "%'";
			//echo("Update Query: ".$updateQuery."<br>");
			$this -> runQuery($updateQuery, $this -> getDBConnection() -> getDBConnection());
		}
		else {
			// If not then delete from post table
			echo("<font color='red'><b>Youtube Video ID: <a href='".$api.$videoId."'>" . $videoId . '</a> Is NOT Valid.</b></font><br>');
			// Add 1 point for pid associated with video in the badids table
			$badIdsQuery = "Insert into BadIDs (id) 
			Select pid from post where postURL like '%watch?v=" . $videoId . "%'
			 on duplicate key update count=count+1;";
			//echo('Bad IDs Query : '.$badIdsQuery."<br>");
			$this -> runQuery($badIdsQuery, $this -> getDBConnection() -> getDBConnection());
			$deleteQuery = "DELETE FROM post WHERE postURL like '%watch?v=" . $videoId . "%'";
			//echo("Delete Query: ".$deleteQuery."<br>");
			$this -> runQuery($deleteQuery, $this -> getDBConnection() -> getDBConnection());

		}

	}

}

$wdbv = new WPTDeleteBadVideos();
?>