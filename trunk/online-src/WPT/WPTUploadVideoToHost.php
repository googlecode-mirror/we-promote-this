<?php
// turn on all errors
//error_reporting(E_ALL);
// turn off all errors
error_reporting(0);
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

    function constructClass() {
        //$this->linearLocation = array ("viddler", "metacafe" );
        $this -> linearLocation = array();
        $this -> handleARGV();
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
        echo("<br><hr/>STARTING Video Uploader. Time: " . date("m-d-y h:i:s A") . "<br>");
        $results = mysql_query("Select p.id, p.location from post as p LEFT JOIN uploadsites as us on p.location=us.location where p.posted=0 and us.working=1 and p.attempts<3 group by p.user_id order by p.lastattempt desc, rand()");
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
                    $this -> getCommandLineHelper() -> run_in_background($cmd, $file);
                    //$this -> upload($id);
                    // For Test Purposes only
                    //sleep ( 2 ); // Sleep 5 secnds before staring another process
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
        // Update task id so I know whats being executed
        $query = "update task set cmd='" . $id . "' where id=" . $this -> taskID;
        //mysql_query($query);
        $this -> getDBConnection() -> threadSafeQuery($query, "LOW_PRIORITY WRITE");

        $query = "Select p.attempts, p.pid, p.location, p.user_id AS userId, us.user_id as userName, us.user_password as userPassword, us.user_wp_id as userWPID, us.active as active , px.port, px.proxy, pc.title, pc.description, k.words From post as p LEFT JOIN users as us ON p.user_id = us.id left join products as pc on p.pid=pc.id left join keywords as k on k.id=p.pid left join proxies as px on p.proxyid=px.id Where p.id=$id";
        //echo("Query: $query<br>");
        $results = mysql_query($query);
        $row = mysql_fetch_assoc($results);
        //echo("Row:<br>");
        //print_r($row);
        //echo "<br>";

        $posted = false;

        $active = (bool)$row["active"];

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

        if ($active == true) {
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
                        echo("<br>Uploading Video $pid to $location for User($userid): " . $userName . "<br>");
                        $uploader -> uploadResponse = $uploader -> upload();
                        // Upload the video
                        $posted = $uploader -> wasUploaded();
                        if ($posted) {
                            $postURL = $uploader -> uploadLocation();
                            $query = "Update post SET posted=1, posttime=NOW(), postURL='$postURL' WHERE id=$id";
                            mysql_query($query);
                            echo("Video Successfully Uploaded!!! Here: <a href='$postURL'>$postURL</a> for User($userid): " . $userName . "<br>");
                            //$this->getLogger()->logInfo ("DB updated with query: $query<br>");
                            if (mysql_errno()) {
                                $this -> getLogger() -> log('Could not update with query: ' . $query . '<br>Mysql Error (' . mysql_errno() . '): ' . mysql_error(), PEAR_LOG_ERR);
                            }
                        } else {
                            $serverResponse = $uploader -> getResponse();
                            if (is_array($serverResponse)) {
                                $serverResponse = print_r($serverResponse, true);
                            }
                            mysql_query("Update post SET error='$error', attempts=$attempts WHERE id=$id");
                            if (mysql_errno()) {
                                $this -> getLogger() -> log('Could not update with query: ' . $query . '<br>Mysql Error (' . mysql_errno() . '): ' . mysql_error(), PEAR_LOG_ERR);
                            }
                            if (stripos($serverResponse, 'AccountDisabled') !== false) {
                                $class = "WPTDeleteUserYoutubeAccount";
                                //$file = $class . ".txt";
                                $file = "WPTUploadVideoToHost.txt";
                                $cmd = $class . ".php uid=$userid";
                                //$this -> getCommandLineHelper() -> run_in_background($cmd, $file);
                                echo("User ($userid): $userName YT account has been disabled. Deleting user.<br>");
                                $this -> getCommandLineHelper() -> startProcess($cmd, $file);
                                $this -> removeUsersTraces($userid);
                            } else {
                                $this -> getLogger() -> logInfo("<font color='red'>Upload Error Posting to " . ucfirst($location) . " for User($userid):\n<br>Server Response: " . $serverResponse . "\n<br>- Video Vars -\n<br><font color='orange'>" . $video . "</font></font>");
                            }
                        }
                    } else {
                        $this -> getLogger() -> logInfo("No $location Uploader for $pid");
                        mysql_query("DELETE FROM post WHERE id=$id");
                    }
                } else {
                    $this -> getLogger() -> logInfo("<font color='brown'>No Valid Video for $pid to post to " . ucfirst($location) . "\n<br>- Video Vars -\n<br><font color='orange'>" . $video . "</font></font>");
                    mysql_query("DELETE FROM post WHERE id=$id");
                    $results = mysql_query("Select p.id, p.location From post as p Where p.location='" . $location . "' AND p.user_id=" . $userid . " AND p.posted=0 AND error is null and p.attempts<3 order by p.lastattempt asc limit 1");
                    $this -> delegateUploadForMysqlResults($results);
                }
            } else {
                $this -> getLogger() -> logInfo("No Product ID Found for $pid");
                mysql_query("Update post SET attempts=$attempts WHERE id=$id");
            }
        } else {
            $this -> getLogger() -> logInfo("User($userid): " . $userName . " is no longer active.<br>");
            mysql_query("Delete from post as p where p.user_id=$userid and posted=0");
            $this -> removeUser($userid);
        }
    }

    function removeUsersTraces($uid) {
        echo("Removing all the users posted videos except the one uploaded within the last 5 hours it was working<br>");
        //mysql_query("Drop table if exists post_bak");
        $this -> runQuery("CREATE Temporary table IF NOT EXISTS post_bak LIKE post;");
        $this -> runQuery("INSERT IGNORE INTO post_bak SELECT * FROM post;");
        // Delete all the users uploaded videos except the ones that were uploaded within the last 5 hours of when the account stoped working
        $this -> getDBConnection()->threadSafeQuery("Delete from post where id IN (
        Select DISTINCT grow.id from
        (SELECT TIMESTAMPDIFF(HOUR, p.posttime ,MAX(p2.posttime)) as last_time,p.id FROM post_bak as p, post_bak as p2 where p.user_id=$uid and p.user_id=p2.user_id and p.posted=1 and p2.posted=1 group by p.user_id, p.pid, p.location having last_time>5) as grow );
        ");
        $this -> getDBConnection()->threadSafeQuery("Delete from post as p where p.user_id=$uid and p.posted=0;");

        $this -> removeUser($uid);

    }

    function removeUser($uid) {
        // Get username
        $query = "Select user_id from users where id=" . $uid;
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        $userName = $row['user_id'];
        
        //echo("Row from username query: $query<br>");
        //var_dump($row);
        //echo("<br>");
        $userName = $row['userName'];
        
        
        // Delete user from users table
        $deleteUserQuery = "Delete from users where id=".$uid;
        $this->runQuery($deleteUserQuery);
        echo("Deleting Users($uid): $userName<br>");
        

        // Delete from Wordpress
        $query = "DELETE from wp_usermeta where umeta_id in
                (Select * from 
                (
                (Select um.umeta_id from wp_usermeta as um where um.meta_value='" . $userName . "') 
                UNION 
                (Select um2.umeta_id from wp_usermeta as um 
                LEFT JOIN wp_usermeta as um2 on (um2.meta_key = CONCAT(um.meta_key,'_password') and um2.user_id=um.user_id)
                where um.meta_value='" . $userName . "'
                )
                ) as grow 
                )";
        echo("Remove user from WP<br>");
        $this -> getDBConnection() -> queryWP($query);
    }

    function runQuery($query) {
        $result = mysql_query($query);
        if (mysql_errno()) {
            $this -> getLogger() -> log('Couldnt execute query: ' . $query . '<br>Mysql Error (' . mysql_errno() . '): ' . mysql_error(), PEAR_LOG_ERR);
        }
        return $result;
    }

}

$videoUploader = new WPTUploadVideoToHost();
?>