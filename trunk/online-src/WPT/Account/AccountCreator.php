<?php
$prependFile = '/home/content/50/6934650/html/pear/includes/prepend.php';
if (file_exists ( $prependFile )) {
	include_once $prependFile;
}

require_once 'Zend/Loader.php';
Zend_Loader::loadClass ( 'Zend_Gdata_ClientLogin' );
Zend_Loader::loadClass ( 'Zend_Gdata_Gapps' );

require_once 'Account.php';

abstract class AccountCreator extends Account {
	
	public $httpClient;
	public $service;
	
	function __construct() {
		try {
			$this->httpClient = $this->getNewHttpClient ( "admin@wepromotethis.com", "Neeuq011$" );
			$this->service = $this->getNewService ( $this->httpClient );
		} catch ( Exception $e ) {
			echo ($e->getMessage () . "<br>");
		}
		//$this->httpClient = $this->getHttpClient ( "admin@chrisqueen.com", "Neeuq011$" );
		$this->constructClass ();
	}
	
	public abstract function constructClass();
	
	public function hasValidHttpClient() {
		return isset ( $this->httpClient );
	}
	
	public function hasValidService() {
		return isset ( $this->service );
	}
	
	public function getNewHttpClient($email, $password) {
		return Zend_Gdata_ClientLogin::getHttpClient ( $email, $password, Zend_Gdata_Gapps::AUTH_SERVICE_NAME );
	
	}
	
	public function getHttpClient() {
		return $this->getHttpClient ();
	}
	
	public function getNewService(Zend_Gdata_HttpClient $client) {
		return new Zend_Gdata_Gapps ( $client, 'wepromotethis.com' );
	}
	
	public function getService() {
		return $this->service;
	}

}

?>