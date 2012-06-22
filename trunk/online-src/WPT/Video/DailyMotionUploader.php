<?php
require_once 'VideoUploader.php';
require_once 'Includes/Dailymotion.php';
class DailyMotionUploader extends VideoUploader {
	public $api;
	public $apiKey;
	public $apiSecret;
	function constructClass() {
		//echo ("Constructing Daily motion class for user:" . $this->userName . "<br>");
		if (isset ( $this->userName ) && isset ( $this->password )) {
			$this->api = new Dailymotion ( );
			$this->apiKey = 'd2fe922f9978c75e00cb';
			$this->apiSecret = 'afb8d17c0d6067370da0375e5f3bf618d6723420';
			$this->api->setGrantType ( Dailymotion::GRANT_TYPE_PASSWORD, $this->apiKey, $this->apiSecret, "read write", array ('username' => $this->userName, 'password' => $this->password ) );
		}
	}
	public function upload() {
		if (isset ( $this->api )) {
			try {
				$url = $this->api->uploadFile ( $this->video->path );
				//$this->getLogger()->logInfo ( "URL: <a href='$url'>$url</a><br>" );
				$response = $this->api->call ( 'video.create', array ('url' => $url ) );
				$videoID = $response ['id'];
				//$this->getLogger()->logInfo ( "Video ID: $videoID<br>" );
				$webTitle = $this->getWebTitle ();
				$relevantVideosFeed = $this->getRelevantVideos ( $webTitle );
				$category = null;
				if (isset ( $relevantVideosFeed )) {
					$categoryMap = $this->getRelevantDailyMotionCategories ( $relevantVideosFeed );
					if (count ( $categoryMap ) > 0) {
						$category = $this->getRelevantCategoryFromCategoryMap ( $categoryMap );
					}
				}
				$videoParameters = array ('id' => $videoID, 'title' => $webTitle, 'description' => $this->getWebDescription (), 'tags' => explode ( ",", $this->getWebKeywords () ), 'published' => true );
				if (isset ( $category ) && strlen ( $category ) > 0) {
					$videoParameters ['channel'] = $category;
				}
				$response = $this->api->call ( 'video.edit', $videoParameters );
				$response = $this->api->call ( 'video.info', array ('id' => $videoID, 'fields' => array ('tiny_url' ) ) );
				$location = $response ['tiny_url'];
				$this->uploadLocation = $location;
				//$this->getLogger()->logInfo ( "Location: " . $location . "<br>" );
			} catch ( DailymotionApiException $e ) {
				$response = $e;
			}
		} else {
			$response = "No username or password to use for uploading";
		}
		return $response;
	}
	function getRelevantVideos($searchTerms, $maxResults = 100) {
		if ($maxResults > 100) {
			$maxResults = 100; // No more than 50 results allowed by Youtube.com
		}
		$response = $this->api->call ( 'video.list', array ('fields' => array ('channel' ), 'limit' => $maxResults, 'sort' => 'relevance', 'search' => $searchTerms ) );
		$relevantVideosList = $response ['list'];
		return $relevantVideosList;
	}
	function getRelevantDailyMotionCategories($videoList) {
		$categoryMap = array ();
		foreach ( $videoList as $videoEntry ) {
			$category = $videoEntry ["channel"];
			if (isset ( $categoryMap [$category] )) {
				$categoryMap [$category] ++;
			} else {
				$categoryMap [$category] = 1;
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
	public function getDescriptionLimit() {
		return 1000;
	}
	public function getKeywordCharLimit() {
		return 455;
	}
	public function getTitleLimit() {
		return 250;
	}
	public function getServiceName() {
		return 'dailymotion';
	}
	
	public function getServiceAbr() {
		return "dm";
	}
}
?>