<?php
require_once ("CBUtils/CBAbstract.php");
require_once ('Account/YoutubeAccount.php');
class WPTDeleteUserYoutubeAccount extends CBAbstract {
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
            //$userid = 52;
            //$class = "WPTDeleteUserYoutubeAccount";
            //$file = $class . ".txt";
            //$cmd = $class . ".php uid=$userid";
            //$this -> getCommandLineHelper() -> startProcess($cmd, $file);
        } else {
            array_shift($argv);
            foreach ($argv as $value) {
                $keyArray = split("=", $value);
                $key = $keyArray[0];
                $keyValue = $keyArray[1];
                switch ($key) {
                    case "uid" :
                        $uid = $keyValue;
                        if (isset($uid)) {
                            $this -> deleteYTAccountForUID($uid);
                        }
                        break;
                    case "username" :
                        $userName = $keyValue;
                        if (isset($userName)) {
                            $this -> deleteYTAccountForUserName($userName);
                        }
                        break;
                }
            }
        }
    }

    function deleteYTAccountForUserName($userName) {
        echo("Deleting YT Account for User ($userName) at " . date("m-d-y h:i:s A") . "<br>");
        $yt = new YoutubeAccount();
        try {
            $yt -> delete(strtolower($userName));
        } catch(Exception $e) {
            echo("Error deleting user ($userName): " . $e -> getMessage() . "<br>");
        }
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
        //echo("Query: $query<br>");
        $this -> getDBConnection() -> queryWP($query);
    }

    function deleteYTAccountForUID($uid) {
        $query = "Select us.user_id as userName from users as us where us.id=" . $uid;
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        $userName = $row['userName'];
        //echo("Found username $userName for User ID: $uid<br>");
        $this -> deleteYTAccountForUserName($userName);
    }

}

$wduya = new WPTDeleteUserYoutubeAccount();
?>