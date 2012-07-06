<?php
$prependFile = '/home/content/50/6934650/html/pear/includes/prepend.php';
if (file_exists ( $prependFile )) {
	include_once $prependFile;
}

require_once 'Zend/Loader.php';
Zend_Loader::loadClass('Zend_Gdata_ClientLogin');
Zend_Loader::loadClass('Zend_Gdata_Gapps');


require_once 'Http/Client.php';
require_once 'Account.php';

abstract class AccountCreator extends Account {
	
	public $httpClient;
    public $service;
	public $proxyHost;
	public $proxyPort;
	public $timout;
	
	function __construct($proxyHost = null, $proxyPort = null) {
		$this->timeout = 115;
		$this->proxyHost = $proxyHost;
		$this->proxyPort = $proxyPort;
		//$this->httpClient = $this->getHttpClient_old ( $this->proxyHost, $this->proxyPort );
		try{
		$this->httpClient = $this->getHttpClient ( "admin@wepromotethis.com", "Neeuq011$" );
        $this->service = $this->getService($this->httpClient);
        }catch(Exception $e){
            echo($e->getMessage()."<br>");
        }
		//$this->httpClient = $this->getHttpClient ( "admin@chrisqueen.com", "Neeuq011$" );
		$this->constructClass ();
	}
	
	public abstract function constructClass();
	public abstract function getLoginUrl();
	
	public function hasValidHttpClient(){
		return isset($this->httpClient);
	}
    
    public function hasValidService(){
        return isset($this->service);
    }
    
    public function getHttpClient($email, $password) {
        return Zend_Gdata_ClientLogin::getHttpClient($email, $password, Zend_Gdata_Gapps::AUTH_SERVICE_NAME);
        
    }
    
    public function getService(Zend_Gdata_HttpClient $client){
        return new Zend_Gdata_Gapps($client,'wepromotethis.com');
    }
	
	public function getHttpClient_old($proxyHost = null, $proxyPort = null) {
		$url = $this->getLoginUrl ();
		$client = null;
		if (isset ( $proxyHost ) && isset ( $proxyPort )) {
			$httpConfig = array ('adapter' => 'Zend_Http_Client_Adapter_Proxy', 'proxy_host' => $proxyHost, 'proxy_port' => $proxyPort, 'maxredirects' => 10, 'timeout' => $this->timeout, 'keepalive' => true );
			try {
				// creates a proxied client to use for authentication
				$client = new Zend_Http_Client ( $url, $httpConfig );
				//echo ( "Using Proxy: $proxyHost port: $proxyPort" );
			} catch ( Zend_Exception $e ) {
				echo ("Error Using Proxy: $proxyHost  port: $proxyPort<br>" . $e->getMessage ());
				$client = new Zend_Http_Client ( $url, array ('maxredirects' => 5, 'timeout' => $this->timeout, 'keepalive' => true ) );
			}
		} else {
			try {
				//echo ( "Not Using Proxy" );
				$client = new Zend_Http_Client ( $url, array ('maxredirects' => 5, 'timeout' => $this->timeout, 'keepalive' => true ) );
			} catch ( Zend_Exception $e ) {
				echo ("Error: " . $e->getMessage ());
			}
		}
		if (isset ( $client )) {
			// To turn cookie stickiness on, set a Cookie Jar
			$client->setCookieJar ();
		}
		return $client;
	}
}

?>