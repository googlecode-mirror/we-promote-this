<?php
require_once 'CBUtils/CBAbstract.php';
require_once 'Video/User.php';

class WPTUploadScheduler extends CBAbstract {

    function constructClass() {

        // Grab videos from local server to remote server that have not been uploaded to all host. (cap at certain file size)

        // Search through the video folder and schedule them to be uploaded by CBVideoUploader.php
        $videoArray = array();

        $videoPath = "./" . RemoteServerVideoLocation;
        //echo("Video Path: $videoPath<br>");

        if (($dh = opendir($videoPath))) {
            while (false !== ($dat = readdir($dh))) {
                if ($dat != "." && $dat != ".." && $dat != ".svn" && $dat != "tmp") {
                    $videoArray[$dat] = $dat;
                }
            }
            closedir($dh);
        }
        //echo("Video Array:<br>");
        //print_r($videoArray);
        //echo("<br><br>");

        // Remove Empty Folders from file system and database
        //$deleteQuery = "Delete from post where posted=0 or attempts>=3;";
        $deleteQuery = '';
        foreach ($videoArray as $index => $videoPID) {
            $folder = $videoPath . $videoPID;
            if ($this -> is_empty_folder($folder)) {
                //echo ("Empty Folder: $folder<br>");
                $this -> rrmdir($folder);
                //$deleteQuery .= "Delete from post where posted=0 and pid='$videoPID';";
                unset($videoArray[$index]);
                // Remove Video from videoArray
            }
        }

        //echo("Delete Query: $deleteQuery<br>");

        //echo("Video Array (After cleaning empty folders:<br>");
        //print_r($videoArray);
        //echo("<br><br>");

        // Get All user IDs from Wordpress
        $users = array();

        $query = "Select u1.meta_value AS 'user_id', u2.meta_value AS 'user_password', u1.user_id AS 'user_wp_id' FROM wp_usermeta AS u1
		JOIN wp_usermeta AS u2 ON (u1.user_id = u2.user_id)
		JOIN wp_usermeta AS u3 ON (u2.user_id = u3.user_id)
		JOIN wp_usermeta AS u4 ON (u3.user_id = u4.user_id)
		
		WHERE u1.meta_key LIKE 'youtube%' AND u1.meta_value IS NOT NULL
		AND u2.meta_key=CONCAT(u1.meta_key,'_password') AND u2.meta_value IS NOT NULL
		AND u3.meta_key='clickbank' AND u3.meta_value IS NOT NULL
		AND u4.meta_key='clickbank_clerk_api_key' AND u4.meta_value IS NOT NULL";
        $result = $this -> getDBConnection() -> queryWP($query);
        while (($row = mysql_fetch_assoc($result))) {
            $user = new User();
            $user -> user_id = $row['user_id'];
            $user -> user_password = $row['user_password'];
            $user -> user_wp_id = $row['user_wp_id'];
            $users[] = $user;
            //echo ("Adding user: $user<br>");
        }
        $userString = implode(',', $users);

        $badVideosCreated = 0;
        if (count($videoArray) > 0) {
            $videoString = "('" . implode("','", $videoArray) . "')";
            // Find videos that have no keywords and delete them
            $badVideosQuery = "SELECT p.id FROM products as p left join keywords AS k using(id) WHERE p.id in $videoString AND (k.id is null OR k.words='[\"{BLANK}\"]' OR CHAR_LENGTH(k.words)<=4)";
            $result = mysql_query($badVideosQuery);
            while (($row = mysql_fetch_assoc($result))) {
                $videoPID = $row['id'];
                $folder = $videoPath . $videoPID;
                $this -> rrmdir($folder);
                unset($videoArray[$videoPID]);
                $badVideosCreated++;
            }
        }

        if (count($videoArray) > 0) {
            $videoString = "('" . implode("'),('", $videoArray) . "')";
            $videoCount = count($videoArray);
            // Create table containg all possible videos to upload
            $createUploadedVideosTableQuery = "DROP TABLE IF EXISTS uploadedVideos;CREATE TEMPORARY TABLE uploadedVideos(id tinytext NOT NULL, PRIMARY KEY(id ( 20 )));INSERT INTO uploadedVideos VALUES $videoString;";

            // Create table containing all user_ids and passwords
            //$createUserTableQuery = "DROP TABLE IF EXISTS users;";
            //CREATE TABLE `users` (
            /*
             $createUserTableQuery .= "CREATE TABLE `users` (
             `id` INT(11) NOT NULL AUTO_INCREMENT,
             `user_id` MEDIUMTEXT NOT NULL,
             `user_password` TEXT NOT NULL,
             `user_wp_id` INT(11) NOT NULL,
             PRIMARY KEY (`id`)
             )
             COLLATE='latin1_swedish_ci'
             ENGINE=MyISAM
             AUTO_INCREMENT=1;
             ";
             */

            // Set all user inactive
            $createUserTableQuery = "UPDATE users set active=0;";
            // Insert all users from Wordpress
            $createUserTableQuery .= "INSERT IGNORE INTO users (user_id, user_password, user_wp_id) VALUES $userString ON DUPLICATE KEY UPDATE active=1 ;";
            //Delete users who no longer exist in wp
            $createUserTableQuery .= "Delete from users where active=0;";

            // ADD all possible video combinations without posttimes
            // one video per user
            //$insertVideoUploadsQuery = "INSERT IGNORE INTO post (pid, user_id, location, proxyid) select uv.id as pid, (SELECT grow.user_id from ((SELECT id as user_id FROM users) UNION ALL (SELECT p.user_id FROM post as p JOIN users as us USING (user_id) WHERE us.active=1 )) as grow GROUP BY grow.user_id  ORDER BY count( grow.user_id ) ASC , rand( )  limit 1) as user_id, uploadsites.location as location , (select id from proxies order by rand() limit 1) as proxyid from uploadedVideos as uv left join keywords as k USING(id), uploadsites where k.id is not null and CHAR_LENGTH(k.words)>4 and k.words!='[\"{BLANK}\"]' and uploadsites.working=1 and uploadsites.type='video';";
            // all videos for every user
            $insertVideoUploadsQuery = "INSERT IGNORE INTO post 
            (pid, user_id, user_wp_id, location, proxyid) 
            select uv.id as pid, us.id as user_id, us.user_wp_id as user_wp_id, uploadsites.location as location , (select id from proxies order by rand() limit 1) as proxyid 
            from 
            uploadedVideos as uv 
            left join keywords as k USING(id), 
            uploadsites, 
            (Select grow.id, grow.user_wp_id, grow.active from
            (
            Select * from users
            UNION ALL 
            Select u1.* from users as u1 left join post as p1 on (u1.id=p1.user_id and p1.posted=1) group by p1.user_id 
            ) as grow
            group by grow.id
            order by count(grow.id) asc, rand()) as us 
            where k.id is not null and CHAR_LENGTH(k.words)>4 and k.words!='[\"{BLANK}\"]' and uploadsites.working=1 and uploadsites.type='video' and us.active=1;";

            //Append all queries
            if (isset($_REQUEST['debug'])) {
                $debugQuery = $deleteQuery . "<br><br>" . $createUploadedVideosTableQuery . "<br><br>" . $createUserTableQuery . "<br><br>" . $insertVideoUploadsQuery;
                die($debugQuery . "<br>Bad Videos Deleted: $badVideosCreated<br>");
            }

            $query = $deleteQuery . $createUploadedVideosTableQuery . $createUserTableQuery . $insertVideoUploadsQuery;

            //die($query);

            $this -> runBatchQuery($query);
            $status = "Product Upload Scheduler: All $videoCount Video(s) Scheduled For Upload. $badVideosCreated bad videos (no keywords) were deleted. Ran On " . date("m-d-y h:i:s A")."<br>";
        } else {
            $status = "No Videos to schedule uploads for.<br>";
        }

        echo($status);
    }

    private function is_empty_folder($dirname) {

        // Returns true if  $dirname is a directory and it is empty
        $result = false;
        // Assume it is not a directory
        if (is_dir($dirname)) {
            $result = true;
            // It is a directory
            $handle = opendir($dirname);
            while (($name = readdir($handle)) !== false) {
                if ($name != "." && $name != ".." && $name != ".svn") {
                    $result = false;
                    // directory not empty
                    break;
                    // no need to test more
                }
            }
            closedir($handle);
        }
        return $result;
    }

    private function rrmdir($path) {
        return is_file($path) ? @unlink($path) : array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path);
    }

    function runBatchQuery($batchQuery) {
        //echo ("<br>Running multi_query update on DB");
        /*
         foreach(explode(";",$batchQuery) as $query)
         {
         mysql_query($query);
         }
         */

        $con = $this -> getDBConnection() -> getMysqliDBConnection();
        $con -> multi_query($batchQuery);
        do {
            //$con->use_result ()->close ();
            // store first result set //
            if (($result = mysqli_store_result($con))) {
                //do nothing since there's nothing to handle
                mysqli_free_result($result);
            }
            //echo "Okay\n";
        } while ( $con->next_result () );
        if ($con -> errno) {
            $this -> getLogger() -> log("MySQL error  : " . $con -> error, PEAR_LOG_ERR);
        }

    }

    function __destruct() {
        parent::__destruct();
    }

}

$obj = new WPTUploadScheduler();
?>