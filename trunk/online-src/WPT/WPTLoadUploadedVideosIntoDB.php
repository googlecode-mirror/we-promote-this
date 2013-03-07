<?php
require_once ("CBUtils/CBAbstract.php");
require_once 'Zend/Loader.php';
// the Zend dir must be in your include_path
Zend_Loader::loadClass('Zend_Gdata_YouTube');

class WPTLoadUploadedVideosIntoDB extends CBAbstract {

    public $yt;

    function __construct() {
        parent::__construct();
    }

    function __destruct() {
        parent::__destruct();
    }

    function constructClass() {
        set_time_limit(0);

        $this -> yt = new Zend_Gdata_YouTube();
        // optionally set version to 2 to retrieve a version 2 feed
        $this -> yt -> setMajorProtocolVersion(2);
        $query = $this -> yt -> newVideoQuery();
        $query -> setMaxResults(50);

        $query = "Select * from users as us order by rand()";
        $results = $this->getDBConnection()->queryDB($query);
        while (($row = $results-> fetch_assoc())) {
            $id = $row['id'];
            $userName = $row['user_id'];
            $userwpid = $row['user_wp_id'];
            $password = $row['user_password'];
            try {
                $this -> getUsersVideos($userName, $id, $userwpid);
            } catch(Exception $e) {
                echo("Excpetion: " . $e -> getMessage() . "<br>");
            }
        }

    }

    function getUsersVideos($userName, $userID, $userWPID) {
        echo("Getting videos for user $userName<br>");
        //&max-results=10
        $videoFeed = $this -> yt -> getuserUploads($userName);
        $this -> updateFromVideoFeed($videoFeed, $userID, $userWPID);

    }

    function updateFromVideoFeed($videoFeed, $userID, $userWPID) {
        $count = 0;
        do {
            foreach ($videoFeed as $videoEntry) {
                $this -> updateFromVideoEntry($videoEntry, $userID, $userWPID);
                $count++;
            }
            $feedCount++;
            try {
                $videoFeed = $videoFeed -> getNextFeed();
            } catch(Exception $e) {
                $videoFeed = null;
            }
        } while(isset($videoFeed));

        //echo("Entry count#: $count\n<br>");
        //echo("Feed count#: $feedCount\n<br>");
        //echo("Last video feed:<br>");
    }

    function updateFromVideoEntry($videoEntry, $userID, $userWPID) {
        //echo 'Video: ' . $videoEntry -> getVideoTitle() . "\n<br>";
        //echo 'Video ID: ' . $videoEntry -> getVideoId() . "\n<br>";
        //echo 'Updated: ' . $videoEntry -> getUpdated() . "\n<br>";
        //echo 'Description: ' . $videoEntry -> getVideoDescription() . "\n<br>";

        //$title = $videoEntry -> getVideoTitle();
        $description = $videoEntry -> getVideoDescription();
        $postTime = date("Y-m-d h:i:s",strtotime($videoEntry -> getUpdated()));
        $postURL = $videoEntry -> getVideoWatchPageUrl();
        //$query = "Select pr.id as pid from prpoducts as pr where pr.title = '" . $title . "'";
        //$results = $this->getDBConnection()->queryDB($query);
        //$row = $results-> fetch_assoc();
        //$pid = $row['pid'];
        
        $pattern = '/WPT\/(.*?)\//i';
        preg_match($pattern, $description, $matches);
        //echo("Matches:<br>");
        //print_r($matches);
        $pid = $matches[1];
        $query = "Insert Ignore into post (pid, user_id, user_wp_id, location, posted, posttime, postURL) Values('$pid',$userID,$userWPID,'youtube',1, '$postTime', '$postURL')";
        //die("Query: $query<br>");
        $this->getDBConnection()->queryDB($query);
    }

}

$obj = new WPTLoadUploadedVideosIntoDB();
?>