<?php

require_once ('VideoUploader.php');
require_once "Includes/osapi/osapi.php";

class MyspaceUploader extends VideoUploader {
	
	public function constructClass() {
	
	}
	
	public function upload() {
		$appKey = '<app key>';
		$appSecret = '<app secret>';
		$userId = '<userId>';
		
		$osapi = new osapi ( new osapiMySpaceProvider (), new osapiOAuth2Legged ( $appKey, $appSecret, $userId ) );
		$batch = $osapi->newBatch ();
		
		// Load file binary data
		$data = file_get_contents ( 'images.jpg' );
		
		$user_params = array ('userId' => '@me', 'groupId' => '@self', 'albumId' => 'myspace.com.album.1224563', 'mediaType' => 'IMAGE', 'mediaItem' => $data, 'contentType' => 'image/jpg' );
		
		// The second option in the $batch->add() assigns a request Id.
		$batch->add ( $osapi->mediaItems->uploadContent ( $user_params ), 'upload_mediaItem' );
		
		// Send all batched commands
		$result = $batch->execute ();
		
		// Demonstrate iterating over a response set, checking for an error & working with the result data. 
		foreach ( $result as $key => $result_item ) {
			if ($result_item instanceof osapiError) {
				echo "<h2>There was a <em>" . $result_item->getErrorCode () . "</em> error with the <em>$key</em> request:</h2>";
				echo "<pre>" . htmlentities ( $result_item->getErrorMessage () ) . "<<nowiki>/</nowiki>pre>";
			} else {
				echo "<h2>Response for the <em>$key</em> request:</h2>";
				echo "<pre>" . htmlentities ( print_r ( $result_item, True ) ) . "<<nowiki>/</nowiki>pre>";
			}
		}
	
	}
	public function getKeywordCharLimit() {
	
	}
	
	public function getTitleLimit() {
	
	}
	
	public function getServiceName() {
		return 'myspace';
	}
	
	public function getServiceAbr() {
		return "my";
	}
	
	public function getDescriptionLimit() {
	
	}
	
	function __destruct() {
	
	}
}

?>