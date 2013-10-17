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
        echo("<br><hr/>STARTING Video Uploader. Time: " . date("m-d-y h:i:s A") . "<br><br>");
        $results = $this -> getDBConnection() -> queryDB("
        Select p.id, p.location 
        from post as p 
        LEFT JOIN uploadsites as us on p.location=us.location
        LEFT JOIN users as u on u.user_wp_id = p.user_wp_id 
        where p.posted=0 and 
        us.working=1 and
        u.active=1 and 
        p.attempts<3 
        group by p.user_id 
        order by p.lastattempt desc, rand()
        ");
        // TODO: add where clause to exclude error is null if files aren't being deleted
        $this -> delegateUploadForMysqlResults($results);
    }

    function delegateUploadForMysqlResults($results) {
        if ($results -> num_rows > 0) {
            $linearIDs = array();
            $class = get_class($this);
            $file = $class . ".txt";
            while (($row = $results -> fetch_assoc())) {
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
        //$query = "update task set cmd='" . $id . "' where id=" . $this -> taskID;
        $query = "update task set cmd='" . $id . "' where id=" . $this -> taskID;
        $this -> getDBConnection() -> queryDB($query);

        $query = "Select p.attempts, p.pid, p.location, p.user_id AS userId, us.user_id as userName, us.user_password as userPassword, us.user_wp_id as userWPID, us.active as active , px.port, px.proxy, pc.title, pc.description, k.words From post as p LEFT JOIN users as us ON p.user_id = us.id left join products as pc on p.pid=pc.id left join keywords as k on k.id=p.pid left join proxies as px on p.proxyid=px.id Where p.id=$id";
        //echo("Query: $query<br>");
        $results = $this -> getDBConnection() -> queryDB($query);
        $row = $results -> fetch_assoc();
        //echo("Row:<br>");
        //print_r($row);
        //echo "<br>";
        $posted = false;
        $active = ( bool )$row["active"];
        $attempts = $row["attempts"] + 1;
        $location = $row["location"];
        $userWPID = $row["userWPID"];
        $userid = $row["userId"];
        $userName = $row["userName"];
        $password = $row["userPassword"];
        $pid = $row["pid"];
        $title = $row["title"];
        $description = $row["description"];
        $keywords = json_decode($row["words"], true);

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
                //$this->getLogger()->logInfo ("PID: $pid");
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
                        echo("Uploading Video $pid to $location for User($userid): " . $userName . "<br>");
                        $uploader -> uploadResponse = $uploader -> upload();
                        // Upload the video
                        $posted = $uploader -> wasUploaded();
                        if ($posted) {
                            $postURL = $uploader -> uploadLocation();
                            $query = "Update post SET posted=1, posttime=NOW(), postURL='" . $this -> getDBConnection() -> getDBConnection() -> real_escape_string($postURL) . "' WHERE id=$id";
                            $rowsAffected = $this -> runQuery($query, $this -> getDBConnection() -> getDBConnection(), true);
                            if ($rowsAffected > 0) {
                                echo("Video Successfully Uploaded!!! Here: <a href='$postURL'>$postURL</a> for User($userid): " . $userName . "<br>");
                            } else {
                                $this -> getLogger() -> logInfo("Error: Video Uploaded but could not update database. Post ID: $id | Video URL: <a href='$postURL'>$postURL</a> for User($userid): " . $userName, PEAR_LOG_ERR);
                                $updateQuery = "Update post SET error='" . $this -> getDBConnection() -> getDBConnection() -> real_escape_string("Video Uploaded but could not update database. | Video URL: " . $postURL) . "', attempts=$attempts WHERE id=$id";
                                $rowsAffected = $this -> runQuery($updateQuery, $this -> getDBConnection() -> getDBConnection(), true);
                                if ($rowsAffected == 0) {
                                    $this -> getLogger() -> logInfo("Could not update post id=$id with error status: Video Uploaded but could not update database. | Video URL: " . $postURL);
                                }
                            }
                        } else {
                            $serverResponse = $uploader -> getResponse();
                            if (is_array($serverResponse)) {
                                $serverResponse = print_r($serverResponse, true);
                            }
                            $updateQuery = "Update post SET error='" . $this -> getDBConnection() -> getDBConnection() -> real_escape_string($serverResponse) . "', attempts=$attempts WHERE id=$id";
                            $rowsAffected = $this -> runQuery($updateQuery, $this -> getDBConnection() -> getDBConnection(), true);
                            if ($rowsAffected == 0) {
                                $this -> getLogger() -> logInfo("Could not update post id=$id with error status: $serverResponse");
                            }
                            if (stripos($serverResponse, 'AccountDisabled') !== false) {
                                echo("User ($userid): $userName YT account has been disabled. Deleting user.<br>");
                                $this -> removeUsersTraces($userid, $userName);
                            } else if (stripos($serverResponse, 'BadAuthentication') !== false) {
                                echo("User ($userid): $userName YT account has bad auhtentication. Deleting user.<br>");
                                $this -> removeUser($userid, $userName);
                            } else if (stripos($serverResponse, 'NoLinkedYouTubeAccount') !== false) {
                                echo("User ($userid): $userName YT account is not linked to this account. Deleting user.<br>");
                                $this -> removeUser($userid, $userName);
                            } else {
                                $this -> getLogger() -> logInfo("<font color='red'>Upload Error Posting to " . ucfirst($location) . " for User($userid):\n<br>Server Response: " . $serverResponse . "\n<br>- Video Vars -\n<br><font color='orange'>" . $video . "</font></font>");
                            }
                        }
                    } else {
                        $this -> getLogger() -> logInfo("No $location Uploader for $pid");
                        $deletePostQuery = "DELETE FROM post WHERE id=$id";
                        $rowsAffected = $this -> runQuery($deletePostQuery, $this -> getDBConnection() -> getDBConnection(), true);
                        if ($rowsAffected == 0) {
                            $this -> getLogger() -> logInfo("Could not delete post id=$id", PEAR_LOG_ERR);
                        }
                    }
                } else {
                    $this -> getLogger() -> logInfo("<font color='brown'>No Valid Video for $pid to post to " . ucfirst($location) . "\n<br>- Video Vars -\n<br><font color='orange'>" . $video . "</font></font>");
                    $deletePostQuery = "DELETE FROM post WHERE id=$id";
                    $rowsAffected = $this -> runQuery($deletePostQuery, $this -> getDBConnection() -> getDBConnection(), true);
                    if ($rowsAffected == 0) {
                        $this -> getLogger() -> logInfo("Could not delete post id=$id", PEAR_LOG_ERR);
                    } else {
                        $results = $this -> getDBConnection() -> queryDB("Select p.id, p.location From post as p Where p.location='" . $location . "' AND p.user_id=" . $userid . " AND p.posted=0 AND error is null and p.attempts<3 order by p.lastattempt asc limit 1");
                        $this -> delegateUploadForMysqlResults($results);
                    }
                }
            } else {
                $this -> getLogger() -> logInfo("No Product ID Found for $pid");
                $updateAttempts = "Update post SET attempts=$attempts WHERE id=$id";
                $rowsAffected = $this -> runQuery($updateAttempts, $this -> getDBConnection() -> getDBConnection(), true);
                if ($rowsAffected == 0) {
                    $this -> getLogger() -> logInfo("Could not update attempts count for post id=$id", PEAR_LOG_ERR);
                }
            }
        } else {
            $this -> getLogger() -> logInfo("User($userid): " . $userName . " is no longer active.");
            $deletePostQuery = "Delete from post as p where p.user_id=$userid and p.posted=0";
            $this -> runQuery($deletePostQuery, $this -> getDBConnection() -> getDBConnection());
            $this -> removeUser($userid, $userName);
        }
        echo("<br>");
        // Just to add a space between video upload log entries
    }

    function removeUsersTraces($uid, $userName) {
        echo("Removing all of users ($uid) posted videos except the one uploaded within the last 5 hours it was working<br>");
        //$this->getDBConnection()->queryDB("Drop table if exists post_bak");
        $this -> runQuery("CREATE Temporary table IF NOT EXISTS post_bak LIKE post;", $this -> getDBConnection() -> getDBConnection());
        $this -> runQuery("INSERT IGNORE INTO post_bak SELECT * FROM post WHERE user_id=$uid;", $this -> getDBConnection() -> getDBConnection());
        // Delete all the users uploaded videos except the ones that were uploaded within the last 5 hours of when the account stoped working
        $deleteQuery1 = "Delete from post where id IN (
        Select DISTINCT grow.id from
        (SELECT TIMESTAMPDIFF(HOUR, p.posttime ,MAX(p2.posttime)) as last_time,p.id FROM post_bak as p, post_bak as p2 where p.user_id=$uid and p.user_id=p2.user_id and p.posted=1 and p2.posted=1 group by p.user_id, p.pid, p.location having last_time>5) as grow );
        ";
        $this -> runQuery($deleteQuery1, $this -> getDBConnection() -> getDBConnection());
        //$this->getDBConnection ()->threadSafeQuery ( $deleteQuery1);
        $deleteQuery2 = "Delete from post as p where p.user_id=$uid and p.posted=0;";
        $this -> runQuery($deleteQuery2, $this -> getDBConnection() -> getDBConnection());
        //$this->getDBConnection ()->threadSafeQuery ( $deleteQuery2 );
        $this -> removeUser($uid, $userName);
    }

    function removeUser($uid, $userName) {
        // Set User to inactive
        $deleteUserQuery = "Update users set active=false WHERE id=" . $uid;
        $affectedRows = $this -> runQuery($deleteUserQuery, $this -> getDBConnection() -> getDBConnection(), true);
        if ($affectedRows > 0) {
            echo("Users($uid): $userName in now inactive<br>");
        } else {
            // Check to see if its already been changed
            $inactiveQuery = "Select * from users where active=false AND id=" . $uid;
            $results = $this -> runQuery($inactiveQuery, $this -> getDBConnection() -> getDBConnection());
            if ($results -> num_rows > 0) {
                echo("Users($uid): $userName in already inactive<br>");
            } else {
                echo("Error changing Users($uid): $userName. Status is still active. | Query used: $deleteUserQuery<br>");
            }
        }

        if (strlen($userName) > 0) {
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
            $affectedRows = $this -> runQuery($query, $this -> getDBConnection() -> getWPDBConnection(), true);
            if ($affectedRows > 0) {
                echo("Removed User($uid): $userName from WP<br>");
            } else {
                // Check to see if the WP user entries are already deleted (i.e they don't exists)
                $wpEntriesQuery = "Select * from 
                (
                (Select um.umeta_id from wp_usermeta as um where um.meta_value='" . $userName . "') 
                UNION 
                (Select um2.umeta_id from wp_usermeta as um 
                LEFT JOIN wp_usermeta as um2 on (um2.meta_key = CONCAT(um.meta_key,'_password') and um2.user_id=um.user_id)
                where um.meta_value='" . $userName . "'
                )
                ) as grow";
                $results = $this -> runQuery($wpEntriesQuery, $this -> getDBConnection() -> getDBConnection());
                if ($results -> num_rows == 0) {
                    echo("User($uid): $userName already removed from WP.<br>");
                } else {
                    echo("Could not remove User($uid): $userName from WP.| Query used: $query <br>");
                }

            }
        } else {
            echo("Could not remove User($uid): from WP because no username was found<br>");
        }
    }
}

$videoUploader = new WPTUploadVideoToHost();
?>