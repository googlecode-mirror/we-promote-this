<?php
ini_set("memory_limit","-1"); // No Memory Limit. Note: This probably is a bad idea
require_once 'Video.php';
abstract class VideoUploader {
	public $video; // video object representing a video on the server to upload
	public $proxyHost; // proxy host ip to use to upload video
	public $proxyPort; // proxy port to use to connect to proxy host
	public $uploadResponse; // The response from the video service after attempting to upload a video
	public $uploadLocation; // The location of the video on service site after successful upload
	public $userName; // username to use for video upload
	public $password; // password to use for video upload
	public $timeout; // Max amount of time httpclient will wait
	function __construct($userName, $password, Video $video, $proxyHost = null, $proxyPort = null) {
		$this->userName = $userName;
		$this->password = $password;
		$this->video = $video;
		$this->video->link.="/".$this->getServiceAbr();
		$this->proxyHost = $proxyHost;
		$this->proxyPort = $proxyPort;
		$this->uploadLocation = null;
		$this->timeout = 60 * 2;
		$this->constructClass ();
	}
	public abstract function constructClass();
	public abstract function upload();
	public abstract function getTitleLimit();
	public abstract function getDescriptionLimit();
	public abstract function getKeywordCharLimit();
	public abstract function getServiceName();
	public abstract function getServiceAbr();
	public function getResponse() {
		return $this->uploadResponse;
	}
	public function wasUploaded() {
		return (isset ( $this->uploadLocation ) && strlen ( $this->uploadLocation ) > 0);
	}
	public function uploadLocation() {
		return $this->uploadLocation;
	}
	public function getWebTitle() {
		$wordsArray = explode ( ",", $this->video->title );
		$title = "";
		foreach ( $wordsArray as $word ) {
			if (strlen ( $title . $word ) <= $this->getTitleLimit () - 3) {
				$title .= $word . " ";
			} else {
				break;
			}
		}
		$title = trim ( $title );
		if (strlen ( $title ) >= $this->getTitleLimit () - 5) {
			$title .= "...";
		}
		return utf8_decode(trim(ucwords($title)));
	}
	public function getWebDescription() {
		// Modify description for SEO advantage
		$linkFiller = $this->video->link . "\n\n" . "[t]...\n\nFind Out More Now: " . $this->video->link . "\n\n This Video was created and uploaded by user of WePromoteThis.com\nGo there now to see how you can make money too.\n".$this->video->hop." \n\n";
		$textArray = explode ( " ", $this->video->description );
		$text = '';
		while ( (strlen ( $linkFiller . $text ) ) < $this->getDescriptionLimit () && count ( $textArray ) > 0 ) {
			$text .= array_shift ( $textArray ) . " ";
		}
		$description = str_ireplace ( "[t]", trim ( $text ), $linkFiller );
		$diff = $this->getDescriptionLimit () - strlen ( $description );
		$keywordFillCount = floor ( $diff / strlen ( $this->video->keywordsAsString ( " " ) ) );
		$keywords = "";
		if (strlen ( $description ) < $this->getDescriptionLimit ()) {
			for($i = 0; $i < $keywordFillCount; $i ++) {
				$keywords .= $this->video->keywordsAsString ( " " ) . " ";
			}
			$karray = explode ( " ", $keywords );
			while ( strlen ( $description . $keywords ) > $this->getDescriptionLimit () ) {
				array_pop ( $karray );
				$keywords = implode ( " ", $karray );
			}
			$description .= $keywords;
		}
		return utf8_decode(trim($description));
	}
	public function getWebKeywords() {
		// Limit the keywords used for tags
		if (strlen ( $this->video->keywordsAsString ( "," ) ) < $this->getKeywordCharLimit ()) {
			$keywords = $this->video->keywordsAsString ( "," );
		} else {
			$keywords = substr ( $this->video->keywordsAsString ( "," ), 0, $this->getKeywordCharLimit () );
			$karray = explode ( " ", $keywords );
			array_pop ( $karray );
			$keywords = implode ( ",", $karray );
		}
		return utf8_decode(trim ( $keywords ));
	}
}
?>