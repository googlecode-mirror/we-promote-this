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
			
			// Add 1 point for pid associated with video in the badids table
			$selectQuery = "Select p.pid from post as p LEFT JOIN products as pr ON pr.id=p.pid where p.postURL like '%watch?v=" . $videoId . "%'";
			$badIdsQuery = "Insert into BadIDs (id) 
			".$selectQuery."
			 on duplicate key update count=count+1;";
			//echo('Bad IDs Query : '.$badIdsQuery."<br>");
			$this -> runQuery($badIdsQuery, $this -> getDBConnection() -> getDBConnection());
			$selectQuery2 = "Select p.pid AS pid, pr.category AS category from post as p LEFT JOIN products as pr ON pr.id=p.pid where p.postURL like '%watch?v=" . $videoId . "%'";
			
			$results = $this -> runQuery($selectQuery2, $this -> getDBConnection() -> getDBConnection());
			$row = $results -> fetch_assoc();
			$pid = $row['pid'];
			$category = $row['category'];
			$postURL = $row["postURL"];
			$deleteQuery = "DELETE FROM post WHERE postURL like '%watch?v=" . $videoId . "%'";
			//echo("Delete Query: ".$deleteQuery."<br>");
			$this -> runQuery($deleteQuery, $this -> getDBConnection() -> getDBConnection());
			echo("<font color='red'><b>Youtube Video ID: <a href='".$api.$videoId."'>" . $videoId . '</a> | PID: '.$pid.' | Category: '.$category.' Is NOT Valid.</b></font><br>');

		}

	}

}

$wdbv = new WPTDeleteBadVideos();
?>