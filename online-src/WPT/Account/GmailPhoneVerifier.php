<?php
set_time_limit ( 0 );
error_reporting ( E_ALL);

$prependFile = '/home/content/50/6934650/html/pear/includes/prepend.php';
if (file_exists ( $prependFile )) {
	require_once $prependFile;
}
require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
Zend_Loader::loadClass ( 'Zend_Mail_Storage_Imap' );
Zend_Loader::loadClass ( 'Zend_Mail_Storage' );
Zend_Loader::loadClass ( 'Zend_Mail_Protocol_Exception' );

class GmailPhoneVerifier extends Zend_Mail_Storage_Imap {
	
	public $mail;
	public $username;
	public $password;
	
	function __construct($username, $password) {
		
		$this->username = $username;
		$this->password = $password;
		$this->connect ();
	
	}
	
	function getPhoneVerificationNumber($matchLimit = 1) {
		
		try {
			
			$verificationNumber = '';
			
			$searchTerm = 'UNSEEN FROM txt.voice.google.com';
			//$searchTerm = $_REQUEST['search'];
			//echo('Search term: '.$searchTerm."<br>");
			//$searchresults = $this->mail->_protocol->search(array($searchTerm));
			$searchresults = $this->_protocol->search(array($searchTerm));
			
			//var_dump($searchresults);
			//die();
			
			foreach ( $searchresults as $messageId ) {
				
				$message = $this->getMessage($messageId);
				//foreach ( $this->mail as $messageId => $message ) {
				
				// Check to see who email is from
				
				
				$body = $message->getContent ();
				
				//echo('*********<br><br>'.$body.'<br><br>**********************<br><br>');
				
				
				$matchFound = false;
				$matches = array ();
				preg_match ( '/Your Google verification code is (.*)\\s/', $body, $matches );
				
				//echo('Matches<br>');
				//var_dump($matches);
				//echo('<br><br>');
				
				
				if (count ( $matches ) > 1 && is_numeric ( trim($matches [1]) )) {
					$verificationNumber = trim($matches [1]);
					$matchFound = true;
				}
				
				if ($matchFound) {
					// get the unique id of the current message before any operations
					$messageUniqueId = $this->mail->getUniqueId ( $messageId );
					
					// first get the message's current imap id
					$currentMessageId = $this->mail->getNumberByUniqueId ( $messageUniqueId );
					
					// Mark it as seen
					$this->mail->setFlags ( $currentMessageId, array (Zend_Mail_Storage::FLAG_SEEN ) );
					
					// then move the email to its destination
					$this->mail->moveMessage ( $currentMessageId, '[Gmail]/Trash' );
					
					$matchLimit = 0;
				
				}
				
				$matchLimit --;
				
				if ($matchLimit <= 0) {
					//echo ("Match Limit Reached<br>");
					break;
				}
			}
		} catch ( Exception $e ) {
			die ($e->getMessage () . "<br>");
			//$this->connect ();
			//$verificationNumber = $this->getPhoneVerificationNumber ( $matchLimit );
		}
		
		return $verificationNumber;
	}
	
	function connect($prefix = null) {
		// connecting with Imap
		$params = array ('host' => 'imap.gmail.com', 'user' => $this->username, 'password' => $this->password, 'ssl' => 'SSL' );
		//$this->mail = new Zend_Mail_Storage_Imap ( $params );
		parent::__construct($params );
		$this->mail = $this;
		
	}

}
//$obj = new GmailPhoneVerifier ( 'frostbyte07@gmail.com', 'Cingularneeuq01' );
//$vn = $obj->getPhoneVerificationNumber();
//echo('Phone Verification Number is: '.$vn);

?>