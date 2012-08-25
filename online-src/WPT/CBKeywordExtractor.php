<?php
require_once 'CBUtils/CBAbstract.php';
require_once 'CBUtils/Includes/class.autokeyword.php';
class CBKeywordExtractor extends CBAbstract {
    public $clickbankID;
    public $updateDBQueryFilename;
    public $lineBreak;
    function __construct() {
        parent::__construct ();
    }
    function __destruct() {
        parent::__destruct ();
    }
    function constructClass() {
        $this->clickbankID = 'xxxxx';
        $this->updateDBQueryFilename = get_class ( $this ) . "-UpdateDBQuery.txt";
        $this->lineBreak = "\n\r";
        $this->handleARGV ();
    }
    function handleARGV() {
        global $argv;
        $keywordLimit = 200;
        if (! isset ( $argv ) || count ( $argv ) <= 1) {
            //echo ("Runnng Default <br>");
            $query = "SELECT id FROM products ORDER BY RAND()";
            $this->update ( $query );
        } else {
            array_shift ( $argv );
            foreach ( $argv as $value ) {
                $keyArray = split ( "=", $value );
                $key = $keyArray [0];
                $keyValue = $keyArray [1];
                switch ($key) {
                    case "update-undone" :
                        //echo ("running undone");
                        $query = "SELECT p.id FROM products as p LEFT JOIN keywords as k ON k.id=p.id WHERE k.id is null and p.id not in (select id from BadIDs where count>=3) order by rand() LIMIT " . $keywordLimit;
                        //$query = "SELECT p.id FROM products as p LEFT JOIN keywords as k ON k.id=p.id WHERE k.id is null and p.id not in (select id from BadIDs where count>=3) ORDER BY gravity DESC, commission DESC, popularityrank DESC, CHAR_LENGTH(description) DESC LIMIT " . $keywordLimit;
                        $this->update ( $query );
                        break;
                    case "runupdatequery" :
                        $this->runUpdateQuery ();
                        break;
                    case "id" :
                        $id = $keyValue;
                        if (isset ( $id )) {
                            $this->runUpdate ( $id );
                        }
                        break;
                    case "update-query" :
                        $query = $keyValue;
                        $this->update ( $query );
                        break;
                    case " " :
                        // DO NOTHING
                        break;
                    default :
                        echo ("Incorrect argument usage. Please use one of the following:\n");
                    case "options" :
                        $options = array ("options" => "Display the options list", "update-undone" => "Update the Query txt file with info for ID found in the products table but not in the keywords table", "update" => "Update the database with keywords for all id found in the products table", "runupdatequery" => "Run all update queries from the ProductUpdateDBQuery.txt file", "id" => "Update the keywords for id into the ProductUpdateDBQuery.txt file" );
                        $space = 0;
                        foreach ( array_keys ( $options ) as $opKey ) {
                            $space = (strlen ( $opKey ) > $space) ? strlen ( $opKey ) : $space;
                        }
                        $space += 5;
                        $spaceString = '';
                        for($i = 0; $i < $space; $i ++) {
                            $spaceString .= " ";
                        }
                        $length = 70;
                        foreach ( $options as $opKey => $opValue ) {
                            $lineCount = 0;
                            $displayArray = array ();
                            $displayArray [$lineCount] = ("\n$opKey" . substr ( $spaceString, strlen ( $opKey ) ));
                            $opValueArray = explode ( " ", $opValue );
                            foreach ( $opValueArray as $opValueWord ) {
                                if ((strlen ( $displayArray [$lineCount] ) + strlen ( $opValueWord )) <= $length) {
                                    $displayArray [$lineCount] .= $opValueWord . " ";
                                } else {
                                    $lineCount ++;
                                    $displayArray [$lineCount] .= $spaceString . $opValueWord . " ";
                                }
                            }
                            echo (implode ( "\n", $displayArray ) . "\n");
                        }
                        break;
                }
            }
        }
    }
    function update($query) {
        //$this->getLogger()->logInfo ( "Updating Keywords using query: " . $query );
        $results = mysql_query ( $query );
        $className = get_class ( $this );
        $file = $className . ".txt";
        while ( ($row = mysql_fetch_array ( $results )) ) {
            $id = $row ["id"];
            $cmd = $className . ".php id=$id";
            $this->getCommandLineHelper ()->run_in_background ( $cmd, $file );
        }
    }
    function runUpdateQuery() {
        //GRAB Query txt from ProductUpdateDBQuery and run mysqli mutli query
        

        $batch = array ();
        
        //$batch[] = file_get_contents ( $this->updateDBQueryFilename );
        $batch [] = "LOAD DATA LOW_PRIORITY LOCAL INFILE '" . mysql_real_escape_string ( realpath ( $this->updateDBQueryFilename ) ) . "' REPLACE INTO TABLE keywords FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\\n' (id,words);";
        
        /*
        $query = array_shift($batch);
        $results = mysql_query($query);
        echo("<br>$query<br><br>Results: ");
        print_r($results);
        echo("<br><br>Errors: ".mysql_error());
        die();
        */
        
        $batch [] = "Insert into BadIDs (id) select id from keywords where CHAR_LENGTH(words)<=4 OR words not like '[\"%' OR words LIKE '%{BLANK}%' on duplicate key update count=count+1;";
        $batch [] = "Delete from keywords where id in (select id from  BadIDs);\nOPTIMIZE TABLE keywords;";
        
        $batchQuery = implode ( "", $batch );
        $this->runBatchQuery ( $batchQuery );
        echo ($batchQuery);
        $this->getDBConnection ()->getMysqliDBConnection ()->close ();
        
        sleep ( 60 ); // wait 1 mintue(s)
        $this->clearFile ( $this->updateDBQueryFilename );
        echo ( "FINISHED Updating Keywords" );
    }
    function runBatchQuery($batchQuery) {
        //echo ("<br>Running multi_query update on DB");
        $con = $this->getDBConnection ()->getMysqliDBConnection ();
        $con->multi_query ( $batchQuery );
        do {
            //$con->use_result ()->close ();
            /* store first result set */
            if (($result = mysqli_store_result ( $con ))) {
                //do nothing since there's nothing to handle
                mysqli_free_result ( $result );
            }
            //echo "Okay\n";
        } while ( $con->next_result () );
        //echo ("Product Update Query: " . $ProductUpdateDBQuery);
        if ($con->errno) {
            $this->getLogger ()->log ( "MySQL error  : " . $con->error, PEAR_LOG_ERR );
        }
    }
    function runUpdate($id) {
        echo ( "Updating Keywords For Product ID: " . $id );
        //echo ("Updating for $id" . $this->lineBreak);
        $hop = "http://" . $this->clickbankID . "." . $id . ".hop.clickbank.net";
        $hop = $this->get_final_url ( $hop );
        //$hop = "http://chrisqueen.com/CB/" . $id;
        //set the length of keywords you like
        $params ['min_word_length'] = 5; //minimum length of single words
        $params ['min_word_occur'] = 2; //minimum occur of single words
        $params ['min_2words_length'] = 3; //minimum length of words for 2 word phrases
        $params ['min_2words_phrase_length'] = 10; //minimum length of 2 word phrases
        $params ['min_2words_phrase_occur'] = 2; //minimum occur of 2 words phrase
        $params ['min_3words_length'] = 3; //minimum length of words for 3 word phrases
        $params ['min_3words_phrase_length'] = 10; //minimum length of 3 word phrases
        $params ['min_3words_phrase_occur'] = 2; //minimum occur of 3 words phrase
        $autokeyword = new autokeyword ( $hop, $params );
        $words = array ();
        // add meta keywords from page
        $metaKeywordsArray = explode ( ",", $autokeyword->getMetaKeywords () );
        array_splice ( $words, count ( $words ), 0, $metaKeywordsArray );
        array_splice ( $words, count ( $words ), 0, $autokeyword->parse_words () );
        array_splice ( $words, count ( $words ), 0, $autokeyword->parse_2words () );
        array_splice ( $words, count ( $words ), 0, $autokeyword->parse_3words () );
        $words = array_unique ( $words );
        $finalWords = array ();
        // Remove Numbers
        foreach ( $words as $word ) {
            $word = trim ( $word );
            if (! is_numeric ( $word ) && strlen ( $word ) > 2) {
                $finalWords [] = $word;
            }
        }
        if (count ( $finalWords ) > 0 && strlen($id)>0) {
            //$keywordQuery = "REPLACE INTO keywords (id,words) VALUES ('$id','" . json_encode ( $finalWords ) . "');";
            $keywordQuery = "'$id','" . json_encode ( $finalWords ) . "'";
            $this->waitForLockAndWrite ( $this->updateDBQueryFilename, $keywordQuery );
            //$this->getLogger ()->logInfo ( "Query for $id: Added!" );
        } else {
            echo ("No content for $id <br>");
            $query = "Insert into BadIDs (id) values ('$id') on duplicate key update count=count+1;";
            mysql_query ( $query );
            if (mysql_errno ()) {
                echo ("MySQL error " . mysql_errno () . ": " . mysql_error () . "\n<br>When executing:<br>\n$query\n<br>");
            }
        }
    }
    function waitForLockAndWrite($file, $txt) {
        $fp = fopen ( $file, "a" );
        fwrite ( $fp, $txt . "\n" );
        fclose ( $fp );
    }
    function clearFile($file) {
        if (file_exists ( $file )) {
            unlink ( $file );
        }
    }
    
    /**
     * get_redirect_url()
     * Gets the address that the provided URL redirects to,
     * or FALSE if there's no redirect. 
     *
     * @param string $url
     * @return string
     */
    function get_redirect_url($url) {
        $redirect_url = null;
        
        $url_parts = @parse_url ( $url );
        if (! $url_parts)
            return false;
        if (! isset ( $url_parts ['host'] ))
            return false; //can't process relative URLs
        if (! isset ( $url_parts ['path'] ))
            $url_parts ['path'] = '/';
        
        $sock = fsockopen ( $url_parts ['host'], (isset ( $url_parts ['port'] ) ? ( int ) $url_parts ['port'] : 80), $errno, $errstr, 30 );
        if (! $sock)
            return false;
        
        $request = "HEAD " . $url_parts ['path'] . (isset ( $url_parts ['query'] ) ? '?' . $url_parts ['query'] : '') . " HTTP/1.1\r\n";
        $request .= 'Host: ' . $url_parts ['host'] . "\r\n";
        $request .= "Connection: Close\r\n\r\n";
        fwrite ( $sock, $request );
        $response = '';
        while ( ! feof ( $sock ) )
            $response .= fread ( $sock, 8192 );
        fclose ( $sock );
        if (preg_match ( '/^Location: (.+?)$/m', $response, $matches )) {
            if (substr ( $matches [1], 0, 1 ) == "/")
                return $url_parts ['scheme'] . "://" . $url_parts ['host'] . trim ( $matches [1] );
            else
                return trim ( $matches [1] );
        
        } else {
            return false;
        }
    
    }
    
    /**
     * get_all_redirects()
     * Follows and collects all redirects, in order, for the given URL. 
     *
     * @param string $url
     * @return array
     */
    function get_all_redirects($url) {
        $redirects = array ();
        while ( $newurl = $this->get_redirect_url ( $url ) ) {
            if (in_array ( $newurl, $redirects )) {
                break;
            }
            $redirects [] = $newurl;
            $url = $newurl;
        }
        return $redirects;
    }
    
    /**
     * get_final_url()
     * Gets the address that the URL ultimately leads to. 
     * Returns $url itself if it isn't a redirect.
     *
     * @param string $url
     * @return string
     */
    function get_final_url($url) {
        $redirects = $this->get_all_redirects ( $url );
        if (count ( $redirects ) > 0) {
            return array_pop ( $redirects );
        } else {
            return $url;
        }
    }
    
    function get_web_page($url) {
        // This example request includes an optional API key which you will need to
        // remove or replace with your own key.
        // Read more about why it's useful to have an API key.
        // The request also includes the userip parameter which provides the end
        // user's IP address. Doing so will help distinguish this legitimate
        // server-side traffic from traffic which doesn't come from an end-user.
        // sendRequest
        // note how referer is set manually
        $options = array (CURLOPT_RETURNTRANSFER => true, // return web page
CURLOPT_FOLLOWLOCATION => true, // follow redirects
CURLOPT_ENCODING => "", // handle all encodings
CURLOPT_REFERER => "www.chrisqueen.com", // set referer on redirect
CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
CURLOPT_TIMEOUT => 120, // timeout on response
CURLOPT_MAXREDIRS => 10 ); // stop after 10 redirects
        $ch = curl_init ( $url );
        curl_setopt_array ( $ch, $options );
        
        $body = curl_exec ( $ch );
        curl_close ( $ch );
        
        //echo ("Body: <br>");
        //var_dump ( $body );
        //echo ("<br>");
        

        // now, process the JSON string
        $json = json_decode ( $body, true );
        // now have some fun with the results...
        return $json ['responseData'] ['results'];
    }

}
$obj = new CBKeywordExtractor ( );
?>