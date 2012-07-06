<?php
error_reporting(E_ALL);
// turn on all errors
require_once 'CBUtils/CBAbstract.php';
require_once 'Video/Video.php';
require_once 'Video/VideoUploader.php';
require_once 'Video/YoutubeUploader.php';
require_once 'Video/DailyMotionUploader.php';
require_once 'Video/MetacafeUploader.php';
require_once 'Video/BliptvUploader.php';
require_once 'Video/ViddlerUploader.php';
require_once 'Video/VimeoUploader.php';
require_once 'Video/RevverUploader.php';

require_once 'CBUtils/Proxy.php';
class WPTUploadVideoToHost extends CBAbstract {
	public $linearLocation;
	// array of uploaders that must be run linearly
	function __construct() {
		parent::__construct();
	}

	function constructClass() {
		//$this->linearLocation = array ("viddler", "metacafe" );
		$this -> linearLocation = array();
		$this -> handleARGV();
	}

	function __destruct() {
		parent::__destruct();
	}

	function handleARGV() {
		global $argv;
		if (!isset($argv) || count($argv) <= 1) {
			$this -> startScheduleUploads();
		} else {
			array_shift($argv);
			foreach ($argv as $value) {
				$keyArray = split("=", $value);
				$key = $keyArray[0];
				$keyValue = $keyArray[1];
				switch ($key) {
					case "id" :
						$id = $keyValue;
						if (isset($id)) {
							$this -> upload($id);
						}
						break;
				}
			}
		}
	}

	function startScheduleUploads() {
		echo("<br><hr/>STARTING Video Uploader. Time: " . date("m-d-y h:i:s A")."<br>");
		$results = mysql_query("Select p.id, p.location from post as p LEFT JOIN uploadsites as us on p.location=us.location where p.posted=0 and us.working=1 and p.attempts<3 group by location, user_id order by p.lastattempt desc, rand()");
		// TODO: add where clause to exclude error is null if files aren't being deleted
		$this -> delegateUploadForMysqlResults($results);
	}

	function delegateUploadForMysqlResults($results) {
		if (mysql_num_rows($results) > 0) {
			$linearIDs = array();
			$class = get_class($this);
			$file = $class . ".txt";
			while (($row = mysql_fetch_assoc($results))) {
				$id = $row["id"];
				$location = $row["location"];
				if (in_array($location, $this -> linearLocation)) {
					$linearIDs[] = $id;
				} else {
					$cmd = $class . ".php id=$id";
					//echo ("\n\nCMD: $cmd\n");
					$this->getCommandLineHelper ()->run_in_background ( $cmd, $file );
					//$this -> upload($id);
					// For Test Purposes only
				}
			}
			foreach ($linearIDs as $id) {
				sleep(30);
				$this -> upload($id);
				// For Test Purposes only
			}
		} else {
			echo("No More Videos To Upload At This Time");
		}
	}

	function upload($id) {
		$query = "Select p.attempts, p.pid, p.location, p.user_id AS userId, us.user_id as userName, us.user_password as userPassword, us.user_wp_id as userWPID, px.port, px.proxy, pc.title, pc.description, k.words From post as p LEFT JOIN users as us ON p.user_id = us.id left join products as pc on p.pid=pc.id left join keywords as k on k.id=p.pid left join proxies as px on p.proxyid=px.id Where p.id=$id";
		//echo("Query: $query<br>");
		$results = mysql_query($query);
		$row = mysql_fetch_assoc($results);
		//echo("Row:<br>");
		//print_r($row);
		//echo "<br>";

		$posted = false;
		$attempts = $row["attempts"] + 1;
		$location = $row["location"];
		$userWPID = $row["userWPID"];
		$userid = $row["userId"];
		$userName = $row["userName"];
		$password = $row["userPassword"];
		$pid = $row["pid"];
		$title = $row["title"];
		$description = $row["description"];
		//echo("Words: ");
		//print_r($row ["words"]);
		//echo "<br>";
		$keywords = json_decode($row["words"], true);
		//echo("Keywords: ");
		//print_r($keywords);
		//echo "<br>";
		//die();

		/*
		 $obj = new Proxy ();
		 $proxySelected = $obj->getRandomProxy ();
		 if (rand ( 0, 1 ) === 1) {
		 $proxy = $proxySelected ["proxy"];
		 $proxyport = $proxySelected ["port"];
		 }
		 */
		//TODO: FIX proxy use uploaders
		$proxy = $row["proxy"] = null;
		$proxyport = $row["port"] = null;

		if (isset($pid)) {
			//$this->getLogger()->logInfo ("PID: $pid<br>");
			//TODO: Determine If Session usage is needed (below)
			// Name The Session
			session_name("$id-$pid-$location-$userid");
			// start a new session
			session_start();
			//TODO: Determine If Session usage is needed (above)
			$video = new Video($pid, $title, $description, $keywords, $userWPID);
            //die($video);
			if ($video -> isValid()) {
				switch ($location) {
					case "youtube" :
						$uploader = new YoutubeUploader($userName, $password, $video, $proxy, $proxyport);
						break;
					case "metacafe" :
						$uploader = new MetaCafeUploader($userName, $password, $video, $proxy, $proxyport);
						break;
					case "viddler" :
						$uploader = new ViddlerUploader($userName, $password, $video);
						break;
					case "blip.tv" :
						$uploader = new BliptvUploader($userName, $password, $video);
						break;
					case "dailymotion" :
						$uploader = new DailyMotionUploader($userName, $password, $video, $proxy, $proxyport);
						break;
					case "vimeo" :
						$uploader = new VimeoUploader($userName, $password, $video);
						break;
					case "revver" :
						$uploader = new RevverUploader($userName, $password, $video);
						break;
					default :
						$uploader = null;
						break;
				}

				if (isset($uploader)) {
					echo("<br>Uploading Video $pid to $location for User($userid): " . $userName."<br>");
					$uploader -> uploadResponse = $uploader -> upload();
					// Upload the video
					$posted = $uploader -> wasUploaded();
					if ($posted) {
						$postURL = $uploader -> uploadLocation();
						$query = "Update post SET posted=1, posttime=NOW(), postURL='$postURL' WHERE id=$id";
						mysql_query($query);
						echo("Video Successfully Uploaded!!! Here: <a href='$postURL'>$postURL</a> for User($userid): " . $userName."<br>");
						//$this->getLogger()->logInfo ("DB updated with query: $query<br>");
						if (mysql_errno()) {
							$this -> getLogger() -> log('Could not update with query: ' . $query . '<br>Mysql Error (' . mysql_errno() . '): ' . mysql_error(), PEAR_LOG_ERR);
						}
					} else {
						$error = $uploader -> getResponse();
						if (is_array($error)) {
							$error = print_r($error);
						}
						$serverResponse = print_r($uploader -> uploadResponse, true);
						$this -> getLogger() -> logInfo("<font color='red'>Upload Error Posting to " . ucfirst($location) . " for User($userid):\n<br>Server Response: " . $serverResponse . "\n<br>" . $error . "\n<br>- Video Vars -\n<br><font color='orange'>" . $video . "</font></font>");
						mysql_query("Update post SET error='$error', attempts=$attempts WHERE id=$id");
						if (mysql_errno()) {
							$this -> getLogger() -> log('Could not update with query: ' . $query . '<br>Mysql Error (' . mysql_errno() . '): ' . mysql_error(), PEAR_LOG_ERR);
						}
					}
				} else {
					$this -> getLogger() -> logInfo("No $location Uploader for $pid");
					mysql_query("DELETE FROM post WHERE id=$id");
				}
			} else {
				$this -> getLogger() -> logInfo("<font color='brown'>No Valid Video for $pid to post to " . ucfirst($location) . "\n<br>- Video Vars -\n<br><font color='orange'>" . $video . "</font></font>");
				mysql_query("DELETE FROM post WHERE id=$id");
				$results = mysql_query("Select p.id, p.location From post as p Where p.location='" . $location . "' AND p.userid=" . $userid . " AND p.posted=0 AND error is null and p.attempts<3 order by p.lastattempt asc limit 1");
				$this -> delegateUploadForMysqlResults($results);
			}
		} else {
			$this -> getLogger() -> logInfo("No Product ID Found for $pid");
			mysql_query("Update post SET attempts=$attempts WHERE id=$id");
		}
	}

}

$videoUploader = new WPTUploadVideoToHost();
?>