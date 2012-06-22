<?php
require_once 'CBAbstract.php';
require_once ('Mail.php');
require_once ('Mail/mime.php');
class Mailer extends CBabstract {
	public $smtp_params;
	function constructClass() {
		// SMTP authentication params
		$this->smtp_params ["host"] = emailhost;
		$this->smtp_params ["port"] = emailport;
		$this->smtp_params ["auth"] = emailauth;
		$this->smtp_params ["username"] = emailusername;
		$this->smtp_params ["dbPassword"] = emailpassword;
	}
	function sendHTMLEmail($sender, $recipient, $subject, $message) {
		// Constructing the email
		$crlf = "\r\n";
		$headers = array ('From' => $sender, 'TO' => $recipient, 'Return-Path' => $sender, 'Subject' => $subject );
		// Creating the Mime message
		$mime = new Mail_mime ( $crlf );
		// Setting the body of the email
		$mime->setHTMLBody ( $message );
		// Set body and headers ready for base mail class
		$body = $mime->get ();
		$headers = $mime->headers ( $headers );
		// Sending the email using smtp
		$mail = & Mail::factory ( "smtp", $this->smtp_params );
		$result = $mail->send ( $recipient, $headers, $body );
		if (PEAR::isError ( $result )) {
			$this->getLogger ()->log ( $result, PEAR_LOG_ERR );
			$result = 0;
		}
		return $result;
	}
}
//$mailer = new Mailer();
//$result = $mailer->sendHTMLEmail("me@chrisqueen.com","chrisqueen@hotmail.com","test","this is a test");
//echo("Result: $result");
?>
