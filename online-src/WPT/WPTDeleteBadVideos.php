<?php
require_once ("CBUtils/CBAbstract.php");
class WPTDeleteBadVideos extends CBAbstract {
	function __construct() {
		parent::__construct();
	}

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
		$videoCheckLimit = 10;
		$query = "SELECT postURL FROM post as p INNER JOIN users as u ON u.id=p.user_id WHERE posted=1 order by lastcheck asc limit " . $videoCheckLimit;
		$results = $this -> runQuery($query, $this -> getDBConnection() -> getDBConnection());
		$class = get_class($this);
		$file = $class . ".txt";
		while (($row = $results -> fetch_assoc())) {
			$postURL = $row["postURL"];
			$postURLParts = explode('watch?v=', $postURL);
			$videoId = array_pop($postURLParts);
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
			echo("Youtube Video ID: " . $videoId . ' IS Valid!!!<br>');
			$updateQuery = "Update post SET lastcheck=now() WHERE postURL like \'%watch?v=" . $videoId . "%\'";
			$this -> runQuery($updateQuery, $this -> getDBConnection() -> getDBConnection());
		}
		else {
			// If not then delete from post table
			echo("Youtube Video ID: " . $videoId . ' Is NOT Valid.<br>');
			$deleteQuery = "DELETE FROM post WHERE postURL like \'%watch?v=" . $videoId . "%\'";
			$this -> runQuery($deleteQuery, $this -> getDBConnection() -> getDBConnection());

		}

	}

}

$wdbv = new WPTDeleteBadVideos();
?>