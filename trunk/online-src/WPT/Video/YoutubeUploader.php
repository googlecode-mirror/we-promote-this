<?php
require_once 'VideoUploader.php';
require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
Zend_Loader::loadClass ( 'Zend_Gdata_YouTube' );
Zend_Loader::loadClass ( 'Zend_Gdata_ClientLogin' );
Zend_Loader::loadClass ( 'Zend_Gdata_App_MediaFileSource');
Zend_Loader::loadClass ( 'Zend_Gdata_App_HttpException' );
Zend_Loader::loadClass ( 'Zend_Gdata_App_Exception' );
Zend_Loader::loadClass ( 'Zend_Exception' );
Zend_Loader::loadClass ( 'Zend_Gdata_HttpClient' );
Zend_Loader::loadClass ( 'Zend_Http_Client' );
Zend_Loader::loadClass ( 'Zend_Http_Client_Adapter_Proxy' );
Zend_Loader::loadClass ( 'Zend_Gdata_HttpAdapterStreamingProxy' );
$path = realpath ( dirname ( __FILE__ ) . "/../" );
require_once $path . '/CBUtils/Categorizer.php';
class YoutubeUploader extends VideoUploader {
	public $httpClient;
	public $applicationId;
	public $clientId;
	public $developerKey;
	public $yt;
	public $userProfileEntry;
	public $xPath;
	public $httpException;
	
	function constructClass() {
		$this->httpClient = NULL;
		$this->applicationId = "WePromoteThis.com";
		$this->clientId = "WePromoteThis.com Upload Client - V1";
		$this->developerKey = "AI39si4YMOXimVNhFRo7aFiCrDMVCvAuyXWChiXMPmf75RuWe-vLLchN0wx_pWigY1A_86dNZWNKaUWQMB7PJT-KcJdRWTyONg";
		$this->httpClient = $this->getHttpClient ( $this->userName, $this->password, $this->proxyHost, $this->proxyPort );
		
		$ytCategoriesURL = 'http://gdata.youtube.com/schemas/2007/categories.cat';
		$ytCategories = file_get_contents ( $ytCategoriesURL );
		$doc = new DOMDocument ( );
		$doc->loadXML ( $ytCategories );
		$this->xPath = new DOMXPath ( $doc );
	}
	public function upload() {
		if (isset ( $this->httpClient )) {
			$response = "No Response From Server";
			$this->yt = new Zend_Gdata_YouTube ( $this->httpClient, $this->applicationId, $this->clientId, $this->developerKey );
			$this->yt->setMajorProtocolVersion ( 2 );
			// create a new VideoEntry object
			$myVideoEntry = new Zend_Gdata_YouTube_VideoEntry ( );
			// create a new Zend_Gdata_App_MediaFileSource object
			//$filesource = $this->yt->newMediaFileSource ( $this->video->path );
			$filesource = new Zend_Gdata_App_MediaFileSource($this->video->path);
			//echo ("Media Source Path " . $this->video->path . "<br>\n");
			$filesource->setContentType ( 'video/mpeg' );
			// set slug header
			$filesource->setSlug ( $this->video->slug );
			// add the filesource to the video entry
			$myVideoEntry->setMediaSource ( $filesource );
			//echo ("Media Source Set<br>\n");
			$myVideoEntry->setVideoTitle ( $this->getWebTitle () );
			//echo ("Video Title Set<br>\n");
			$myVideoEntry->setVideoDescription ( $this->getWebDescription () );
			//echo ("Description Set<br>\n");
			

			//TODO: Figure out how to set video response access as allowed
			

			// The category must be a valid YouTube category!
			$relevantVideosFeed = $this->getRelevantVideos ( $this->getWebTitle () );
			$category = "Entertainment";
			if (isset ( $relevantVideosFeed )) {
				$categoryMap = $this->getRelevantYoutubeCategories ( $relevantVideosFeed );
				if (count ( $categoryMap ) > 0) {
					$category = $this->getRelevantCategoryFromCategoryMap ( $categoryMap );
				}
			}
			// Check to see if category is deprecated then use Category Chooser to find best category
			if (! $this->isValidCategory ( $category )) {
				//echo ("$category Is not valid. Looking for another valid category");
				$categorizer = new Categorizer ( $this->video->pid );
				$categorizer->chooseCategory ( $this->getPossibleCategories () );
				$category = $categorizer->getPossCategoryName ();
			}
			
			//echo ("Choosen Category: $category<br>");
			

			$myVideoEntry->setVideoCategory ( $category );
			//echo ("Category Set<br>\n");
			// Set keywords. Please note that this must be a comma-separated string
			// and that individual keywords cannot contain whitespace
			//$keywords = $this->getWebKeywords ();
			//echo ("Web Keywords: $keywords<br>\n");
			$keywords = $this->getYoutubeModifiedKeywords ();
			//echo ( "Mod Keywords: $keywords<br>\n" );
			if (strlen ( $keywords ) > 0) {
				$myVideoEntry->SetVideoTags ( $keywords );
			}
			//echo ("Tags Set<br>\n");
			// upload URI for the currently authenticated user
			$uploadUrl = 'http://uploads.gdata.youtube.com/feeds/api/users/default/uploads';
			// try to upload the video, catching a Zend_Gdata_App_HttpException, 
			// if available, or just a regular Zend_Gdata_App_Exception otherwise
			try {
				//echo ("Inserting Video Entry<br>\n");
				$newEntry = $this->yt->insertEntry ( $myVideoEntry, $uploadUrl, 'Zend_Gdata_YouTube_VideoEntry' );
				$response = $this->getVideoState ( $newEntry );
				$this->uploadLocation = $newEntry->getVideoWatchPageUrl ();
			} catch ( Exception $except ) {
				//echo ("Exception Thrown<br>\n");
				$response = $except->getMessage ();
			}
		} else {
			$response = "No Http Client to upload video for user: " . $this->userName . "| Youtube HttpClient Response: " . $this->httpException;
			//echo ($response . "<br>");
		}
		return $response;
	}
	function getPossibleCategories() {
		$possibleCategories = array ();
		$xQuery = $this->xPath->query ( "//atom:category" );
		foreach ( $xQuery as $node ) {
			if (strcasecmp ( $node->lastChild->nodeName, "yt:deprecated" ) !== 0) {
				//echo ("Possible Category: " . $node->getAttribute("label") . "<br>");
				$possibleCategories [] = $node->getAttribute ( "label" );
			}
		}
		return $possibleCategories;
	}
	
	function isValidCategory($categoryName) {
		$valid = false;
		$xQuery = $this->xPath->query ( "//atom:category[contains(@label,'" . $categoryName . "')]" );
		if ($xQuery->length > 0) {
			$valid = true;
			$atom = $xQuery->item ( 0 );
			//echo ("Found Atom " . $atom->getAttribute ( "label" ) . "<br>");
			if (strcasecmp ( $atom->lastChild->nodeName, "yt:deprecated" ) === 0) {
				$valid = false;
				
			//echo ("Category $categoryName is deprecated<br>");
			}
		}
		return $valid;
	}
	
	function getVideoState($videoEntry) {
		$state = $videoEntry->getVideoState ();
		if ($state) {
			return 'Upload status for video ID ' . $videoEntry->getVideoId () . ' is ' . $state->getName () . ' - ' . $state->getText () . "\n";
		} else {
			return "Not able to retrieve the video status information yet. " . "Please try again later.\n";
		}
	}
	
	function getRelevantVideos($searchTerms, $maxResults = 50) {
		if ($maxResults > 50) {
			$maxResults = 50; // No more than 50 results allowed by Youtube.com
		}
		try {
			$yt = new Zend_Gdata_YouTube ( );
			$yt->setMajorProtocolVersion ( 2 );
			$query = $yt->newVideoQuery ();
			$query->setOrderBy ( 'relevance' );
			$query->setSafeSearch ( 'none' );
			$query->setMaxResults ( $maxResults );
			$query->setVideoQuery ( $searchTerms );
			// Note that we need to pass the version number to the query URL function
			// to ensure backward compatibility with version 1 of the API.
			$videoFeed = $yt->getVideoFeed ( $query->getQueryUrl ( 2 ) );
			return $videoFeed;
		} catch ( Zend_Gdata_App_HttpException $httpException ) {
			//echo ("App HttpException Thrown<br>\n");
			$response = $httpException->getRawResponseBody ();
		} catch ( Zend_Gdata_App_Exception $e ) {
			//echo ("App Exception Thrown<br>\n");
			$response = $e->getMessage ();
		} catch ( Exception $except ) {
			//echo ("Exception Thrown<br>\n");
			$response = $except->getMessage ();
		}
		echo ("Error: $response<br>");
		return null;
	}
	function getRelevantYoutubeCategories($videoFeed) {
		$categoryMap = array ();
		foreach ( $videoFeed as $videoEntry ) {
			$category = $videoEntry->getVideoCategory ();
			if ($this->isValidCategory ( $category )) {
				if (isset ( $categoryMap [$category] )) {
					$categoryMap [$category] ++;
				} else {
					$categoryMap [$category] = 1;
				}
			}
		}
		return $categoryMap;
	}
	function getRelevantCategoryFromCategoryMap($categoryMap) {
		arsort ( $categoryMap );
		$keysArray = array_keys ( $categoryMap );
		$category = $keysArray [0];
		return $category;
	}
	private function getHttpClient($userEmail, $password, $proxyHost = null, $proxyPort = null, $tries = 3) {
		$authenticationURL = Zend_Gdata_YouTube::CLIENTLOGIN_URL;
		$service = Zend_Gdata_YouTube::AUTH_SERVICE_NAME;
		if (isset ( $userEmail ) && isset ( $password )) {
			try {
				if (isset ( $proxyHost ) && isset ( $proxyPort )) {
					$httpConfig = array ('adapter' => 'Zend_Gdata_HttpAdapterStreamingProxy', 'proxy_host' => $proxyHost, 'proxy_port' => $proxyPort, 'maxredirects' => 5, 'timeout' => $this->timeout, 'keepalive' => true );
					//$httpConfig = array ('adapter' => 'Zend_Http_Client_Adapter_Proxy', 'proxy_host' => $proxy->proxy, 'proxy_port' => $proxy->port,'maxredirects' => 10, 'timeout' => 120, 'keepalive' => true );
					try {
						// creates a proxied client to use for authentication
						$clientp = new Zend_Gdata_HttpClient ( $authenticationURL, $httpConfig );
						// To turn cookie stickiness on, set a Cookie Jar
						$clientp->setCookieJar ();
						// authenticate
						//$httpClient = Zend_Gdata_ClientLogin::getHttpClient ( $userEmail, $password, $service, $clientp );
						$httpClient = Zend_Gdata_ClientLogin::getHttpClient ( $userEmail, $password, $service, $clientp, 'WePromoteThis.com', null, null, $authenticationURL );
						// set the proxy information back into the client
						// necessary due to http://framework.zend.com/issues/browse/ZF-1920
						$httpClient->setConfig ( $httpConfig );
						//echo ("Using Proxy: $proxyHost port: $proxyPort<br>");
					} catch ( Zend_Gdata_App_HttpException $e ) {
						//var_dump ( $e );
						//echo ("Error Using Proxy: $proxyHost  port: $proxyPort<br>" . $e->getMessage () . "<br>");
						$httpClient = Zend_Gdata_ClientLogin::getHttpClient ( $userEmail, $password, $service, null, 'WePromoteThis.com', null, null, $authenticationURL );
					}
				} else {
					//echo ("Not Using Proxy");
					$httpClient = Zend_Gdata_ClientLogin::getHttpClient ( $userEmail, $password, $service, null, 'WePromoteThis.com', null, null, $authenticationURL );
				}
			} catch ( Exception $e ) {
				//echo ("Error getting Youtube HttpClient: " . $e->getMessage () . "<br>");
				$this->httpException = $e->getMessage ();
			}
		} else {
			//echo ("Credentials missing. Username: $userEmail | Password length: " . strlen ( $password ) . " <br>");
		}
		if (! isset ( $httpClient ) && $tries > 0) {
			sleep ( 30 );
			$httpClient = $this->getHttpClient ( $userEmail, $password, $proxyHost, $proxyPort, -- $tries );
		}
		return $httpClient;
	}
	function getYoutubeModifiedKeywords() {
		$keywordsArray = $this->video->keywordsAsArray ();
		$modifiedArray = array ();
		while ( $this->arrayBytes ( $modifiedArray ) < $this->getKeywordsTotalByteLimit () && count ( $keywordsArray ) > 0 ) {
			$word = array_shift ( $keywordsArray );
			if(strlen($word)==1){
				continue;
			}
			
			$wordByteCount = $this->arrayBytes ( array ($word ) );
			if ($this->getKeywordByteMin () <= $wordByteCount && $wordByteCount <= $this->getKeywordByteMax ()) {
				$modifiedArray [] = $word;
			} else if ($wordByteCount > $this->getKeywordByteMax ()) {
				$newordArray = explode ( " ", $word );
				do {
					array_pop ( $newordArray );
					$wordByteCount = $this->arrayBytes ( $newordArray );
				} while ( $wordByteCount > $this->getKeywordByteMax () && count ( $newordArray ) > 0 );
				if (count ( $newordArray ) > 0 && $this->getKeywordByteMin () <= $wordByteCount) {
					$modifiedArray [] = implode ( " ", $newordArray );
				}
			}
		}
		
		// Remove Duplicates
		$modifiedArray = array_unique ( $modifiedArray );
		
		while ( $this->arrayBytes ( $modifiedArray ) > $this->getKeywordsTotalByteLimit () ) {
			array_pop ( $modifiedArray );
		}
		
		//$totalBytes = $this->arrayBytes ( $modifiedArray );
		//echo ("Modified Keywords<br>");
		//print_r ( $modifiedArray );
		//die ( " - Bytes Count: " . $totalBytes );
		

		return implode ( ",", $modifiedArray );
	}
	function arrayBytes(array $array) {
		$bytesCount = 0;
		$oneWordArray = array ();
		$multiWordArray = array ();
		foreach ( $array as $word ) {
			if (stripos ( $word, " " ) !== false) {
				$multiWordArray [] = '"' . $word . '"';
			} else {
				$oneWordArray [] = '"' . $word . '"';
			}
		}
		if (count ( $oneWordArray ) > 0) {
			$bytesCount += $this->strBytes ( implode ( ",", $oneWordArray ), "UTF-8" );
		}
		if (count ( $multiWordArray ) > 0) {
			$bytesCount += $this->strBytes ( implode ( ",", $multiWordArray ), "UTF-16" );
		}
		return $bytesCount;
	}
	
	function strBytes($str, $encoding) {
		$encoding = "UTF-8";
		return mb_strlen ( $str, $encoding );
	
	}
	
	function strBytesOld($str) {
		// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
		// Number of characters in string
		$strlen_var = strlen ( $str );
		// string bytes counter
		$d = 0;
		/*
      * Iterate over every character in the string,
      * escaping with a slash or encoding to UTF-8 where necessary
      */
		for($c = 0; $c < $strlen_var; ++ $c) {
			$ord_var_c = ord ( $str {$d} );
			//$ord_var_c = ord ( $str {$c} );
			switch (true) {
				case (($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)) :
					// characters U-00000000 - U-0000007F (same as ASCII)
					$d ++;
					break;
				case (($ord_var_c & 0xE0) == 0xC0) :
					// characters U-00000080 - U-000007FF, mask 110XXXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d += 2;
					break;
				case (($ord_var_c & 0xF0) == 0xE0) :
					// characters U-00000800 - U-0000FFFF, mask 1110XXXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d += 3;
					break;
				case (($ord_var_c & 0xF8) == 0xF0) :
					// characters U-00010000 - U-001FFFFF, mask 11110XXX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d += 4;
					break;
				case (($ord_var_c & 0xFC) == 0xF8) :
					// characters U-00200000 - U-03FFFFFF, mask 111110XX
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d += 5;
					break;
				case (($ord_var_c & 0xFE) == 0xFC) :
					// characters U-04000000 - U-7FFFFFFF, mask 1111110X
					// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
					$d += 6;
					break;
				default :
					$d ++;
			}
		}
		return $d;
	}
	public function getKeywordsTotalByteLimit() {
		return 500;
	}
	public function getKeywordByteMin() {
		return 2;
	}
	public function getKeywordByteMax() {
		return 30;
	}
	public function getDescriptionLimit() {
		return 2000;
	}
	public function getKeywordCharLimit() {
		return 500;
	}
	public function getTitleLimit() {
		return 100;
	}
	public function getServiceName() {
		return 'youtube';
	}
	
	public function getServiceAbr() {
		return "yt";
	}
}

?>