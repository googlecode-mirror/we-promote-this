<?php

error_reporting ( E_ALL );

require_once 'AccountCreator.php';
$path = realpath ( dirname ( __FILE__ ) . "/../" );
require_once $path . '/CBUtils/DeCaptcha.php';
require_once $path . '/CBUtils/Name.php';
require_once $path . '/CBUtils/Proxy.php';

require_once 'Zend/Loader.php';
// the Zend dir must be in your include_path
Zend_Loader::loadClass ( 'Zend_Gdata_YouTube' );
Zend_Loader::loadClass ( 'Zend_Gdata_ClientLogin' );
class YoutubeAccount extends AccountCreator {
	private $acceptedTOSClient;
	private $lastParameters;
	public $responseCount;
	function constructClass($dbConnect = null) {
		$this->responseCount = 0;
		if (isset ( $dbConnect )) {
			$person = new Name ( $this->get );
			$this->firstName = $person->firstName;
			$this->lastName = $person->lastName;
		} else {
			$this->firstName = "Joe" . rand ( 1000, 40000 );
			$this->lastName = "Blow" . rand ( 1000, 40000 );
		}
	}
	function create($username, $password, $tries = 0) {
		$this->userName = $username;
		$this->email = $username . "@wepromotethis.com";
		$this->password = $password;
		
		if ($this->hasValidHttpClient () && $this->hasValidService ()) {
			try {
				$this->service->createUser ( $this->userName, $this->firstName, $this->lastName, $this->password );
				$this->setValid ( $this->acceptTOSForUser () );
				if (! $this->isValid ()) {
					echo ("TOS not valid<br>");
					$this->service->deleteUser ( $this->userName );
					if ($tries < 3) {
						$this->create ( $username . "t" . $tries . "r" . rand ( 0, 100 ), $password, ++ $tries );
					}
				} else {
					echo ("TOS Valid<br>");
					$this->createYTChannel ();
				}
			} catch ( CaptchaRequiredException $e ) {
				$src = $e->getCaptchaUrl ();
				echo ("Please visit $src<br>");
				$deCaptcha = new DeCaptcha ( 'frostbyte07', 'Neeuq011$' );
				$captchaText = $deCaptcha->getCatchaText ( $src );
				$this->service->setUserCredentials ( $this->email, $this->password, $e->getCaptchaToken (), $captchaText );
			} catch ( AuthenticationException $e ) {
				echo ("Authentication exception: " . $e->getMessage ());
			} catch ( Zend_Gdata_Gapps_ServiceException $e ) {
				// Set the user to null if not found
				if ($e->hasError ( Zend_Gdata_Gapps_Error::ENTITY_DOES_NOT_EXIST )) {
				} else {
					// Outherwise, just print the errors that occured and exit
					foreach ( $e->getErrors () as $error ) {
						echo "Error encountered: " . $error->getReason () . " (" . $error->getErrorCode () . ")<br>";
					}
				}
			} catch ( Exception $e ) {
				echo ("Error while creating user: " . $e->getMessage ());
			}
		} else {
			echo ("No valid http client or valid service");
		}
	}
	function acceptTOSForUser() {
		$accepted = false;
		
		// echo ("Gathered Username: $userName | Password: $password<br><br>");
		$client = null;
		$tries = 2;
		while ( ! isset ( $client ) && $tries > 0 ) {
			// $p = new Proxy ( );
			// $p = $p->getRandomProxy ();
			$p = array (
					"proxy" => null,
					"port" => null 
			);
			$client = $this->getPlainHttpClient ( "http://youtube.com", $p ['proxy'], $p ['port'] );
			
			$tries --;
			if (! isset ( $client )) {
				sleep ( 20 );
			}
		}
		$clientResponse = $client->request ( Zend_Http_Client::POST );
		$response = $clientResponse->getBody ();
		
		// echo ("Response 1:<br>");
		// var_dump ( $response );
		// echo ("<br><br><br>");
		
		$doc = new DOMDocument ();
		$doc->loadHTML ( $response );
		$xpath = new DOMXPath ( $doc );
		
		// get sign in button
		foreach ( $xpath->query ( '//a[text()="Sign In"]' ) as $node ) {
			$signInUrl = $node->getAttribute ( "href" );
		}
		// echo ("Sign in URL: $signInUrl<br><br>");
		
		$client->setUri ( $signInUrl );
		$clientResponse = $client->request ( Zend_Http_Client::POST );
		$response = $clientResponse->getBody ();
		
		// echo ("Response 2:<br>");
		// var_dump ( $response );
		// echo ("<br><br><br>");
		
		$doc = new DOMDocument ();
		$doc->loadHTML ( $response );
		$xpath = new DOMXPath ( $doc );
		
		// get Form action
		foreach ( $xpath->query ( '//form[contains(@id,"gaia_loginform")]' ) as $node ) {
			$action = $node->getAttribute ( "action" );
		}
		
		// echo ("Form submit = $action<br><br>");
		$parameters = array (
				"Email" => $this->email,
				"Passwd" => $this->password,
				"signIn" => "Sign in",
				"service" => "youtube" 
		);
		
		// get all hidden inputs in form and add to parameters
		foreach ( $xpath->query ( '//input[contains(@type,"hidden")]' ) as $node ) {
			$parameters [$node->getAttribute ( "name" )] = $node->getAttribute ( "value" );
		}
		
		// echo ("Parameters:<br>");
		// var_dump ( $parameters );
		// echo ("<br><br>");
		
		$client->setUri ( $action );
		$client->setParameterPost ( $parameters );
		$clientResponse = $client->request ( Zend_Http_Client::POST );
		$response = $clientResponse->getBody ();
		// echo ("Response 3:<br>");
		// var_dump ( $response );
		// echo ("<br><br><br>");
		
		$solved = false;
		$tries = 0;
		$deCaptcha = new DeCaptcha ( 'frostbyte07', 'Neeuq011$' );
		do {
			
			// Solve Captcha
			$doc = new DOMDocument ();
			$doc->loadHTML ( $response );
			$xpath = new DOMXPath ( $doc );
			$src = null;
			foreach ( $xpath->query ( '//img[contains(@alt,"Visual verification")]' ) as $node ) {
				$src = $node->getAttribute ( "src" );
			}
			
			if (strlen ( $src ) > 0) {
				if ($tries > 0) {
					$deCaptcha->reportLastCatchaIncorrect ();
				}
				echo ("Solving captcha<br>");
				
				$captchaText = $deCaptcha->getCatchaText ( $src );
				// echo ("Captcha Image: <img src='$src'><br>Text:
				// $captchaText<br><br>");
				$parameters = array (
						"toscaptcha" => $captchaText,
						"accept" => "I accept. Continue to my account." 
				);
				
				// get all hidden inputs in form and add to parameters
				foreach ( $xpath->query ( '//input[contains(@type,"hidden")]' ) as $node ) {
					$parameters [$node->getAttribute ( "name" )] = $node->getAttribute ( "value" );
				}
				
				// echo ("Parameters:<br>");
				// var_dump ( $parameters );
				// echo ("<br><br>");
				
				$client->setParameterPost ( $parameters );
				$clientResponse = $client->request ( Zend_Http_Client::POST );
				$response = $clientResponse->getBody ();
				
				// echo ("Response 4:<br><pre>$response</pre>");
				// echo ("<br><br><br>");
			} else {
				echo ("Captcha Solved<br>");
				$solved = true;
				$accepted = true;
				$this->acceptedTOSClient = $client;
				$this->storeResponse ( $response );
				
				$doc = new DOMDocument ();
				$doc->loadHTML ( $response );
				$xpath = new DOMXPath ( $doc );
				
				$this->lastParameters = array ();
				
				// get all hidden inputs in form and add to parameters
				foreach ( $xpath->query ( '//input[contains(@type,"hidden")]' ) as $node ) {
					$this->lastParameters [$node->getAttribute ( "name" )] = $node->getAttribute ( "value" );
				}
			}
			$tries ++;
		} while ( ! $solved && $tries < 10 );
		return $accepted;
	}
	function createYTChannel() {
		if ($this->isValid () && isset ( $this->acceptedTOSClient )) {
			
			echo ("Creating yt channel<br>");
			
			$client = $this->acceptedTOSClient;
			
			// Go to create channel url
			$createChannelURL = "http://www.youtube.com/create_channel";
			$client->setUri ( $createChannelURL );
			$client->setParameterPost ( $this->lastParameters );
			// $this->httpClient->setHeaders ( array ('Accept-Encoding: gzip,
			// deflate', 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64;
			// rv:2.0) Gecko/20100101 Firefox/4.0', 'Accept:
			// text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
			// 'Accept-Language: en-us,en;q=0.5', 'Accept-Charset:
			// ISO-8859-1,utf-8;q=0.7,*;q=0.7', 'Keep-Alive: 115', 'Connection:
			// keep-alive', 'Referer: http://www.youtube.com', 'Host:
			// www.youtube.com', 'Cookie:
			// __utma=173272373.199096328.1305393518.1305393518.1305525473.2;
			// __utmz=173272373.1305393518.1.1.utmcsr=mail.google.com|utmccn=(referral)|utmcmd=referral|utmcct=/mail/u/0/;
			// __utmc=173272373; GoogleAccountsLocale_session=en;' ) );
			$this->httpClient->setHeaders ( array (
					'Accept-Encoding: gzip, deflate',
					'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0) Gecko/20100101 Firefox/4.0',
					'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'Accept-Language: en-us,en;q=0.5',
					'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7',
					'Keep-Alive:	115',
					'Connection: keep-alive',
					'Referer: http://www.youtube.com',
					'Host: www.youtube.com' 
			) );
			$clientResponse = $client->request ( Zend_Http_Client::POST );
			$response = $clientResponse->getBody ();
			
			// echo ("Response 1:<br>$response");
			// echo ("<br><br><br>");
			$this->storeResponse ( $response );
			
			$doc = new DOMDocument ();
			$doc->loadHTML ( $response );
			$xpath = new DOMXPath ( $doc );
			
			$genders = array (
					"m",
					"f" 
			);
			$gender = $genders [array_rand ( $genders )];
			
			$userNameAccepted = false;
			
			$parameters = array (
					"username" => $this->userName,
					"country" => "US",
					"gender" => $gender,
					"find_me_via_email" => "agreed" 
			);
			
			// get all hidden inputs in form and add to parameters
			foreach ( $xpath->query ( '//input[contains(@type,"hidden")]' ) as $node ) {
				$parameters [$node->getAttribute ( "name" )] = $node->getAttribute ( "value" );
			}
			
			// Set user name, country, gender, and aggree to find via email
			$client->setParameterPost ( $parameters );
			$clientResponse = $client->request ( Zend_Http_Client::POST );
			$response = $clientResponse->getBody ();
			
			// echo ("Response loop:<br>$response");
			// echo ("<br><br><br>");
			$this->storeResponse ( $response );
			
			$doc = new DOMDocument ();
			$doc->loadHTML ( $response );
			$xpath = new DOMXPath ( $doc );
			
			// get all inputs in form and add to parameters
			foreach ( $xpath->query ( '//input' ) as $node ) {
				$parameters [$node->getAttribute ( "name" )] = $node->getAttribute ( "value" );
			}
			
			// Accept final selection of yt options
			$client->setParameterPost ( $parameters );
			$clientResponse = $client->request ( Zend_Http_Client::POST );
			$response = $clientResponse->getBody ();
			// echo ("Response final :<br>$response");
			// echo ("<br><br><br>");
			$this->storeResponse ( $response );
			
			// echo ("YT Channel Created<br>");
		} else {
			echo ("No valid user or accepted tos");
		}
	}
	function storeResponse($reponse) {
		$this->responseCount ++;
		
		//$file = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . "../Logs/Response_" . $this->responseCount . ".html";
		$file = dirname ( __FILE__ ) . DIRECTORY_SEPARATOR . "Response_" . $this->responseCount . ".html";
		echo ("Storing response to :" . $file . "<br>");
		$fp = fopen ( $file, "a" );
		fwrite ( $fp, $reponse );
		fclose ( $fp );
	}
	public function getPlainHttpClient($url, $proxyHost = null, $proxyPort = null) {
		$client = null;
		if (isset ( $proxyHost ) && isset ( $proxyPort )) {
			$httpConfig = array (
					'adapter' => 'Zend_Http_Client_Adapter_Proxy',
					'proxy_host' => $proxyHost,
					'proxy_port' => $proxyPort,
					'maxredirects' => 10,
					'timeout' => 120,
					'keepalive' => true 
			);
			try {
				// creates a proxied client to use for authentication
				$client = new Zend_Http_Client ( $url, $httpConfig );
				// echo ( "Using Proxy: $proxyHost port: $proxyPort" );
			} catch ( Zend_Exception $e ) {
				echo ("Error Using Proxy: $proxyHost  port: $proxyPort<br>" . $e->getMessage ());
				$client = new Zend_Http_Client ( $url, array (
						'maxredirects' => 5,
						'timeout' => 120,
						'keepalive' => true 
				) );
			}
		} else {
			try {
				// echo ( "Not Using Proxy" );
				$client = new Zend_Http_Client ( $url, array (
						'maxredirects' => 5,
						'timeout' => 120,
						'keepalive' => true 
				) );
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

$yt = new YoutubeAccount ();
$username = "wptAA" . rand ( 1000, 40000 );
$password = 'Tpw2012' . rand ( 0, 1000 ) . '$';
$yt->create ( $username, $password );
if ($yt->isValid ()) {
	echo ("Created Users:<br>" . $yt->userName . " | Password: " . $yt->password . "<br>");
} else {
	echo ("Couldnt create valid user");
}

?>