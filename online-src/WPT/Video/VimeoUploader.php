<?php

require_once 'VideoUploader.php';
require_once 'Includes/vimeo.php';

class VimeoUploader extends VideoUploader {
	
	public $api_key;
	public $api_secret;
	public function constructClass() {
		$this->api_key = 'f461763118714671457dac267748152a';
		$this->api_secret = 'ff7a90d68844dbd6';
	}
	
	public function upload() {
		$vimeo = new phpVimeo ( $this->api_key, $this->api_secret, 'ACCESS_TOKEN', 'ACCESS_TOKEN_SECRET' );
		try {
			$video_id = $vimeo->upload ( $this->video->path );
			if ($video_id) {
				$vimeo->call ( 'vimeo.videos.setTitle', array ('title' => $this->getWebTitle (), 'video_id' => $video_id ) );
				$vimeo->call ( 'vimeo.videos.setDescription', array ('description' => $this->getWebDescription (), 'video_id' => $video_id ) );
				$vimeo->call ( 'vimeo.videos.addTags', array ('tags' => $this->getWebKeywords (), 'video_id' => $video_id ) );
				$this->uploadLocation = "http://vimeo.com/" . $video_id;
				$response = "Video Upload Successful";
			} else {
				$response = "Video file did not exist!";
			}
		} catch ( VimeoAPIException $e ) {
			$response = "Encountered an API error -- code {$e->getCode()} - {$e->getMessage()}";
		}
		return $response;
	}
	
	public function getDescriptionLimit() {
		return 2000;
	}
	
	public function getKeywordCharLimit() {
		return 455;
	}
	
	public function getServiceName() {
		return 'vimeo';
	}
	
	public function getTitleLimit() {
		return 100;
	}
	
	public function getServiceAbr() {
		return "vm";
	}

}

?>