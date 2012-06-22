<?php
require_once 'VideoUploader.php';
require_once "Includes/blipPHP.php";
class BliptvUploader extends VideoUploader {
	public $blip;
	function constructClass() {
		/** Create blipPHP object. **/
		$this->blip = new blipPHP ( $this->userName, $this->password );
	}
	public function upload() {
		$videoPath = substr ( $this->video->path, 2 );
		/** Upload file **/
		$response = $this->blip->upload ( $videoPath, $this->getWebTitle (), $this->getWebDescription (), $this->getWebKeywords () );
		$results = $response->xpath ( '/response/payload/asset/links/link[0]/text()' );
		if (! isset ( $results [0] )) {
			$results = $response->xpath ( '/response/payload/asset/links/link[1]/text()' );
		}
		//var_dump ( $results );
		$this->uploadLocation = $results [0];
		//$this->getLogger()->logInfo ("Location: " . $results [0] . "<br>");
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
		return 'blip.tv';
	}
	
	public function getServiceAbr() {
		return "bt";
	}
}
?>