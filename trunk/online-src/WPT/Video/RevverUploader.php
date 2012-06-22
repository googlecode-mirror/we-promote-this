<?php
require_once 'VideoUploader.php';
include ('Includes/RevverPHP/xmlrpc-2_1/lib/xmlrpc.inc');
include ('Includes/RevverPHP/class.RevverAPI.php');

class RevverUploader extends VideoUploader {
	function constructClass() {
	}
	public function upload() {
		$url = 'https://api.revver.com/xml/1.0?login=' . $this->userName . '&passwd=' . $this->password;
		//$url = 'https://api.revver.com/xml/1.0';
		echo ("Url: $url<br>");
		$api = new RevverAPI ( $url ); // production
		// $api = new RevverAPI ( 'https://api.staging.revver.com/xml/1.0?login=revtester&passwd=testacct' ); // staging
		$results = $api->callRemote ( 'user.authenticate', $this->userName, $this->password );
		echo '<pre>';
		var_dump ( $results );
		echo '</pre><br>';
		$count = 1;
		$token = $api->callRemote ( 'video.getUploadTokens', $count );
		echo ("token:<br>");
		var_dump ( $token );
		echo ("<br>");
		$options = array ('url' => $this->video->link, 'author' => ArticleAuthor, 'description' => $this->getWebDescription () );
		$videoID = $api->callRemote ( 'video.create', $token, $this->getWebTitle (), $this->getWebKeywords (), 1, $options );
		echo ("videoID:<br>");
		var_dump ( $videoID );
		echo ("<br>");
		$response = $api->callRemote ( 'video.get', $videoID, array ('flashMediaUrl' ) );
		echo ("video.get response:<br>");
		var_dump ( $response );
		echo ("<br>");
		
		$this->uploadLocation = $response ['flashMediaUrl'];
		return $response;
	}
	public function getDescriptionLimit() {
		return 2000;
	}
	public function getKeywordCharLimit() {
		return 455;
	}
	public function getTitleLimit() {
		return 100;
	}
	public function getServiceName() {
		return 'revver';
	}
	
	public function getServiceAbr() {
		return "rv";
	}
}
?>