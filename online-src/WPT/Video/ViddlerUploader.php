<?php
require_once 'VideoUploader.php';
require_once 'Includes/phpviddler.php';
class ViddlerUploader extends VideoUploader {
	public $api_key;
	function constructClass() {
		$this->api_key = '9vdxql6zn7g51exc61p0';
	}
	public function upload() {
		$viddler = new Viddler_V2 ( $this->api_key );
		if (isset ( $this->userName ) && isset ( $this->password )) {
			$user = $viddler->viddler_users_auth ( array ('user' => $this->userName, 'password' => $this->password ) );
			$params = array ('sessionid' => $user ['auth'] ['sessionid'], 'title' => $this->getWebTitle (), 'tags' => $this->getWebKeywords (), 'description' => $this->getWebDescription (), 'file' => '@' . $this->video->path );
			$prepare = $viddler->viddler_videos_prepareUpload ( array ('sessionid' => $user ['auth'] ['sessionid'] ) );
			$response = $viddler->viddler_videos_upload ( $params, $prepare ['upload'] ['endpoint'] );
			//print_r ( $response );
			if (is_array ( $response )) {
				$videoArray = $response ['video'];
				if (is_array ( $videoArray )) {
					$this->uploadLocation = $videoArray ['url'];
				}
			}
		} else {
			$response = "No username or password to use for uploading";
		}
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
		return 'viddler';
	}
	
	public function getServiceAbr() {
		return "vd";
	}
}
?>