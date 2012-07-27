<?php

require_once 'CBUtils/CBAbstract.php';
require_once 'CBUtils/CommentCreator.php';

//error_reporting ( E_ERROR );
// Errors only

require_once 'Zend/Loader.php';
// the Zend dir must be in your include_path
Zend_Loader::loadClass('Zend_Gdata_YouTube');
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_App_HttpException');
Zend_Loader::loadClass('Zend_Gdata_App_Exception');
Zend_Loader::loadClass('Zend_Exception');
Zend_Loader::loadClass('Zend_Gdata_HttpClient');
Zend_Loader::loadClass('Zend_Http_Client');
Zend_Loader::loadClass('Zend_Http_Client_Adapter_Proxy');
Zend_Loader::loadClass('Zend_Gdata_HttpAdapterStreamingProxy');
Zend_Loader::loadClass('Zend_Gdata_YouTube_Extension_Username');

class WPTYTBoost extends CBAbstract {

    public $applicationId;
    public $clientId;
    public $developerKey;
    public $ytAccounts;

    function constructClass() {
        $this -> handleARGV();
    }

    function handleARGV() {
        global $argv;
        if (!isset($argv) || count($argv) <= 1) {
            $this -> startBoostingYT();
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
                            $this -> init($uid);
                        }
                        break;
                }
            }
        }
    }

    function startBoostingYT() {
        echo("<br><hr/>STARTING YT Booster. Time: " . date("m-d-y h:i:s A") . "<br>");
        $class = get_class($this);
        $file = $class . ".txt";
        $query = "Select id from users where active=1;";
        $result = mysql_query($query);
        while (($row = mysql_fetch_assoc($result))) {
            $uid = $row['id'];
            $cmd = $class . ".php uid=$uid";
            $this -> getCommandLineHelper() -> run_in_background($cmd, $file);
        }
    }

    function init($uid) {
        $this -> applicationId = "WePromoteThis.com";
        $this -> clientId = "WePromoteThis.com ";
        $this -> developerKey = "AI39si4YMOXimVNhFRo7aFiCrDMVCvAuyXWChiXMPmf75RuWe-vLLchN0wx_pWigY1A_86dNZWNKaUWQMB7PJT-KcJdRWTyONg";
        $this -> ytAccounts = array();
        $query = "Select user_id, user_password from users where active=1 AND id!=$uid;";
        $result = mysql_query($query);
        while (($row = mysql_fetch_assoc($result))) {
            $this -> ytAccounts[$row['user_id']] = $row['user_password'];
        }
        $accountTotal = count($this -> ytAccounts);
        $this -> boostYtAccount($uid);

    }

    function boostYtAccount($uid) {
        $query = "Select user_id, user_password from users where id=$uid;";
        $result = mysql_query($query);
        $row = mysql_fetch_assoc($result);
        $userName = $row['user_id'];
        $password = $row['user_password'];
        //echo("Username: $userName | Password: $password<br>");
        $httpClient = $this -> getHttpClient($userName, $password);
        $yt = new Zend_Gdata_YouTube($httpClient, $this -> applicationId, $this -> clientId, $this -> developerKey);
        $yt -> setMajorProtocolVersion(2);
        $cc = new CommentCreator();

        echo("<font color='green'>Acting as user $userName</font><br>");

        // For all other users
        foreach ($this -> ytAccounts as $otherUserName => $password) {

            echo("<font color='orange'>Parsing video feed for user $otherUserName</font><br>");

            $videoFeed = $yt -> getuserUploads($otherUserName);
            $feedCount = 0;
            do {
                $feedCount++;
                foreach ($videoFeed as $videoEntry) {
                    $videoURL = $videoEntry -> getVideoWatchPageUrl();
                    $videoID = $videoEntry -> getVideoId();
                    //$query = "Select id, boosted from post where postURL='".$videoURL."'";
                    $query = "Select id from boosted where video_id='" . $videoID . "' AND user_id=$uid";
                    //echo($query . "<br>");
                    $result = mysql_query($query);
                    $resultCount = mysql_num_rows($result);
                    $boosted = true;
                    if ($resultCount == 0) {
                        $boosted = false;
                    }
                    // If videoEntry has not already been boosted
                    if (!$boosted) {
                        echo("<font color='purple'><b>Boosting video " . $videoEntry -> getVideoTitle() . "</b><br>");
                        // Add a 5 star rating to videos
                        if ($this -> add5StarRating($yt, $videoEntry)) {
                            echo("Adding 5 stars to video.<br>");
                        } else {
                            exit(0);
                        }
                        // Add a comment to other users videos

                        if ($this -> addCommentToVideo($yt, $videoEntry, $cc -> getComment())) {
                            echo("Adding comments to video.<br>");
                        } else {
                            exit(0);
                        }

                        // Add one of your videos to a response
                        // TODO: Maybe in the future if the respones video can be approaved automatically
                        //if($this->addVideoResponse($yt, $videoEntry, $videoResponseEntry)){
                        //  echo("Adding video response to video.<br>");
                        //}else{
                        //exit(0);
                        //}
                        echo("</font>");
                        $query = "Insert Ignore INTO boosted (user_id, video_id) Values ($uid,'$videoID')";
                        mysql_query($query);
                        echo("<b><a href='$videoURL'>$videoURL</a> NOW boosted!!!</b><br>");
                        sleep(rand(10, 30));
                    } else {
                        echo("<u><a href='$videoURL'>$videoURL</a> already boosted.</u><br>");
                    }
                }
                try {
                    $videoFeed = $videoFeed -> getNextFeed();
                    //var_dump($videoFeed);
                    //echo("<br><br><br>");
                } catch(Exception $e) {
                    //echo("NextFeedError: " . $e -> getMessage() . "<br>");
                    $videoFeed = null;
                    echo("<font color='blue'>User $otherUserName feed count: $feedCount</font><br>");
                }
            } while(isset($videoFeed));

            // Subscribe to other users yt accounts
            if ($this -> subscribeToUserChannel($yt, $otherUserName)) {
                echo("<font color='red'>Subscribbing to users channel.</font><br>");
            } else {
                continue;
            }
        }
    }

    function addVideoResponse($yt, $videoEntry, $videoResponseEntry) {
        $successful = true;
        $responsesFeedUrl = $videoEntry -> getVideoResponsesLink() -> getHref();
        try {
            $yt -> insertEntry($videoResponseEntry, $responsesFeedUrl);
        } catch (Exception $e) {
            if (stripos($e -> getMessage(), 'too_many_recent_calls') !== false) {
                $successful = false;
            } else if (stripos($e -> getMessage(), 'Posting too fast') !== false) {
                sleep(rand(10, 30));
                $successful = $this -> addVideoResponse($yt, $videoEntry, $videoResponseEntry);
            } else {
                $successful = false;
                echo "Error adding video response to video: " . $e -> getMessage() . "\n<br>";
            }
        }
        if ($successful) {
            sleep(rand(5, 25));
        }
        return $successful;
    }

    function addCommentToVideo($yt, $videoEntry, $comment) {
        $successful = true;
        $newComment = $yt -> newCommentEntry();
        $newComment -> content = $yt -> newContent() -> setText($comment);
        $commentFeedPostUrl = $videoEntry -> getVideoCommentFeedUrl();
        try {
            $updatedVideoEntry = $yt -> insertEntry($newComment, $commentFeedPostUrl, 'Zend_Gdata_YouTube_CommentEntry');
        } catch (Exception $e) {
            if (stripos($e -> getMessage(), 'too_many_recent_calls') !== false) {
                $successful = false;
            } else if (stripos($e -> getMessage(), 'Posting too fast') !== false) {
                sleep(rand(10, 30));
                $successful = $this -> addCommentToVideo($yt, $videoEntry, $comment);
            } else {
                $successful = false;
                echo "Error adding comment to video: " . $e -> getMessage() . "\n<br>";
            }
        }
        if ($successful) {
            sleep(rand(5, 25));
        }
        return $successful;
    }

    function subscribeToUserChannel($yt, $channel) {
        $successful = true;
        $subscriptionsFeedUrl = "http://gdata.youtube.com/feeds/api/users/default/subscriptions";
        $newSubscription = $yt -> newSubscriptionEntry();
        $newSubscription -> setUsername(new Zend_Gdata_YouTube_Extension_Username($channel));
        try {
            $yt -> insertEntry($newSubscription, $subscriptionsFeedUrl);
        } catch (Exception $e) {
            if (stripos($e -> getMessage(), 'too_many_recent_calls') !== false) {
                $successful = false;
            } else if (stripos($e -> getMessage(), 'Posting too fast') !== false) {
                sleep(rand(10, 30));
                $successful = $this -> subscribeToUserChannel($yt, $channel);
            } else {
                $successful = false;
                echo "Error subscribing to channel $channel: " . $e -> getMessage() . "\n<br>";
            }
        }
        if ($successful) {
            sleep(rand(5, 25));
        }
        return $successful;
    }

    function add5StarRating($yt, $videoEntryToRate) {
        $successful = true;
        $videoEntryToRate -> setVideoRating(5);
        $ratingUrl = $videoEntryToRate -> getVideoRatingsLink() -> getHref();
        try {
            $ratedVideoEntry = $yt -> insertEntry($videoEntryToRate, $ratingUrl, 'Zend_Gdata_YouTube_VideoEntry');
        } catch (Exception $e) {
            if (stripos($e -> getMessage(), 'too_many_recent_calls') !== false) {
                $successful = false;
            } else if (stripos($e -> getMessage(), 'Posting too fast') !== false) {
                sleep(rand(10, 30));
                $successful = $this -> add5StarRating($yt, $videoEntryToRate);
            } else {
                $successful = false;
                echo "Error adding 5 star rating: " . $e -> getMessage() . "\n<br>";
            }
        }
        if ($successful) {
            sleep(rand(5, 25));
        }
        return $successful;
    }

    private function getHttpClient($userEmail, $password, $proxyHost = null, $proxyPort = null, $tries = 3) {
        $authenticationURL = Zend_Gdata_YouTube::CLIENTLOGIN_URL;
        $service = Zend_Gdata_YouTube::AUTH_SERVICE_NAME;
        if (isset($userEmail) && isset($password)) {
            try {
                if (isset($proxyHost) && isset($proxyPort)) {
                    $httpConfig = array('adapter' => 'Zend_Gdata_HttpAdapterStreamingProxy', 'proxy_host' => $proxyHost, 'proxy_port' => $proxyPort, 'maxredirects' => 5, 'timeout' => 120, 'keepalive' => true);
                    //$httpConfig = array ('adapter' => 'Zend_Http_Client_Adapter_Proxy', 'proxy_host' => $proxy->proxy, 'proxy_port' => $proxy->port,'maxredirects' => 10, 'timeout' => 120, 'keepalive' => true );
                    try {
                        // creates a proxied client to use for authentication
                        $clientp = new Zend_Gdata_HttpClient($authenticationURL, $httpConfig);
                        // To turn cookie stickiness on, set a Cookie Jar
                        $clientp -> setCookieJar();
                        // authenticate
                        //$httpClient = Zend_Gdata_ClientLogin::getHttpClient ( $userEmail, $password, $service, $clientp );
                        $httpClient = Zend_Gdata_ClientLogin::getHttpClient($userEmail, $password, $service, $clientp, 'WePromoteThis.com', null, null, $authenticationURL);
                        // set the proxy information back into the client
                        // necessary due to http://framework.zend.com/issues/browse/ZF-1920
                        $httpClient -> setConfig($httpConfig);
                        //echo ("Using Proxy: $proxyHost port: $proxyPort<br>");
                    } catch ( Zend_Gdata_App_HttpException $e ) {
                        //var_dump ( $e );
                        //echo ("Error Using Proxy: $proxyHost  port: $proxyPort<br>" . $e->getMessage () . "<br>");
                        $httpClient = Zend_Gdata_ClientLogin::getHttpClient($userEmail, $password, $service, null, 'WePromoteThis.com', null, null, $authenticationURL);
                    }
                } else {
                    //echo ("Not Using Proxy");
                    $httpClient = Zend_Gdata_ClientLogin::getHttpClient($userEmail, $password, $service, null, 'WePromoteThis.com', null, null, $authenticationURL);
                }
            } catch ( Exception $e ) {
                //echo ("Error getting Youtube HttpClient: " . $e->getMessage () . "<br>");
                $this -> httpException = $e -> getMessage();
            }
        } else {
            //echo ("Credentials missing. Username: $userEmail | Password length: " . strlen ( $password ) . " <br>");
        }
        if (!isset($httpClient) && $tries > 0) {
            sleep(30);
            $httpClient = $this -> getHttpClient($userEmail, $password, $proxyHost, $proxyPort, --$tries);
        }
        return $httpClient;
    }

}

$obj = new WPTYTBoost();
?>