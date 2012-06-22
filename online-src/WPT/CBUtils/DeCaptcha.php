<?php
require_once 'Includes/deathbycaptcha.php';

class DeCaptcha {
	public $client;
	public $lastCaptcha;
	function __construct($userName, $password) {
		//$this->client = new DeathByCaptcha_SocketClient ( $userName, $password );
		$this->client = new DeathByCaptcha_HttpClient ( $userName, $password );
		$this->client->is_verbose = false;
	}
	
	function getBalance() {
		return $this->client->balance;
	}
	
	function getCatchaText($captchaFile) {
		if (! is_resource ( $captchaFile )) {
			$captchaFile = fopen ( $captchaFile, "r" );
			
			/*
			$data = file_get_contents ( $captchaFile );
			$tmp = tmpfile ();
			file_put_contents ( $tmp, $data );
			$captchaFile = $tmp;
			*/
		}
		$text = null;
		if (($captcha = $this->client->decode ( $captchaFile, DeathByCaptcha_Client::DEFAULT_TIMEOUT ))) {
			//echo "CAPTCHA {$captcha['captcha']} solved: {$captcha['text']}\n";
			$text = $captcha ['text'];
			$this->lastCaptcha = $captcha;
		
		//$this->reportLastCatchaIncorrect();
		}
		return $text;
	}
	
	function reportLastCatchaIncorrect() {
		// Report if the CAPTCHA was solved incorrectly. Make sure the CAPTCHA
		// was in fact solved incorrectly!
		$this->client->report ( $this->lastCaptcha ['captcha'] );
	}
}
?>