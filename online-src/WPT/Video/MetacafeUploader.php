<?php
require_once 'VideoUploader.php';
class MetaCafeUploader extends VideoUploader {
	public $httpClient;
	function constructClass() {
		$this->httpClient = NULL;
		//$this->timeout = 30; // Increase Timeout
		$this->httpClient = $this->getHttpClient ( $this->userName, $this->password, $this->proxyHost, $this->proxyPort );
	}
	public function upload() {
		$response = "No Response From Server Yet";
		if (isset ( $this->httpClient )) {
			// HAS TO BE UNIQUE LOCATION FOR SOME REASON
			$tmpFolder = dirname ( __FILE__ ) . "/CBCreatedVideos/tmp" . "/";
			if (! file_exists ( $tmpFolder )) {
				mkdir ( $tmpFolder, 0777, true );
			}
			$file = $this->video->path;
			$fileName = $this->getServiceName () . "-" . date ( "m-d-y-His-" ) . rand () . "-" . $this->video->slug;
			$tmpFile = $tmpFolder . $fileName;
			//Copy file to tmp folder
			if (! copy ( $file, $tmpFile )) {
				$response = "failed to copy $file to $tmpFile\n";
			} else {
				$urlFile = 'http://' . RemoteHost . RemoteHostRootFolder . RemoteServerVideoLocation . "tmp/" . $fileName;
				//echo("Need to upload file: <a href='$urlFile'>$urlFile</a>");
				$this->httpClient->setUri ( 'http://www.metacafe.com/submit/data/' );
				//Started Upload video
				$this->httpClient->setParameterPost ( array ('contentFilters' => 'everyone', 'fileURL' => $urlFile, 'itemChannels' => '7', 'itemDescription' => $this->getWebDescription (), 'itemTitle' => $this->getWebTitle (), 'searchKeywords' => $this->getWebKeywords (), 'status' => 'started' ) );
				$this->httpClient->setFileUpload ( $file, 'fileURL' );
				try {
					$clientResponse = $this->httpClient->request ( Zend_Http_Client::POST );
					//Save And Complete
					$this->httpClient->setParameterPost ( array ('status' => 'completeAndSaved' ) );
					$clientResponse = $this->httpClient->request ( Zend_Http_Client::POST );
					// Get ItemID
					$this->httpClient->setParameterPost ( array ('status' => 'itemID' ) );
					$clientResponse = $this->httpClient->request ( Zend_Http_Client::POST );
					$json = json_decode ( $clientResponse->getBody (), true );
					$this->uploadLocation = $json ['responseParams'] ['itemURL'];
				} catch ( Exception $e ) {
					$response = "Error: " . $e->getMessage () . "<br>";
				}
			}
		} else {
			$response = "No Http Client to upload video";
		}
		return $response;
	}
	private function rrmdir($path) {
		return is_file ( $path ) ? @unlink ( $path ) : array_map ( array ($this, 'rrmdir' ), glob ( $path . '/*' ) ) == @rmdir ( $path );
	}
	private function getHttpClient($userEmail, $password, $proxyHost = null, $proxyPort = null, $retry = 5) {
		$url = "https://secure.metacafe.com/account/login/";
		$client = null;
		if (isset ( $userEmail ) && isset ( $password )) {
			if (isset ( $proxyHost )) {
				$httpConfig = array ('adapter' => 'Zend_Http_Client_Adapter_Proxy', 'proxy_host' => $proxyHost, 'proxy_port' => $proxyPort, 'maxredirects' => 5, 'timeout' => $this->timeout, 'keepalive' => true );
				try {
					// creates a proxied client to use for authentication
					$client = new Zend_Http_Client ( $url, $httpConfig );
					//echo ("Using Proxy: $proxyHost port: $proxyPort");
				} catch ( Zend_Exception $e ) {
					//echo("Error Using Proxy: $proxyHost  port: $proxyPort<br>" . $e->getMessage());
					$client = new Zend_Http_Client ( $url, array ('maxredirects' => 5, 'timeout' => $this->timeout, 'keepalive' => true ) );
				}
			} else {
				//echo("Not Using Proxy");
				$client = new Zend_Http_Client ( $url, array ('maxredirects' => 5, 'timeout' => $this->timeout, 'keepalive' => true ) );
			}
			// To turn cookie stickiness on, set a Cookie Jar
			$client->setCookieJar ();
			$client->setParameterPost ( array ('email' => $userEmail, 'password' => $password, 'submit' => 'Sign+In', 'pageToLoad' => '1', 'remember' => 'on' ) );
			// Authenticate Login
			try {
				$client->request ( Zend_Http_Client::POST );
			} catch ( Zend_Exception $e ) {
				//echo("Error: Logging In - " . $e->getMessage() . "<br>");
				$client = null;
			}
		}
		if (! isset ( $client ) && $retry > 0) {
			sleep(10);
			$client = $this->getHttpClient ( $userEmail, $password, $proxyHost, $proxyPort, $retry -- );
		}
		return $client;
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
		return 'metacafe';
	}
	
	public function getServiceAbr() {
		return "mc";
	}
}
?>