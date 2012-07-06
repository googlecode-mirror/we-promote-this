<?php
set_time_limit ( 0 );
error_reporting ( E_ALL);

$prependFile = '/home/content/50/6934650/html/pear/includes/prepend.php';
if (file_exists ( $prependFile )) {
	include_once $prependFile;
}

require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
Zend_Loader::loadClass ( 'Zend_Mail_Storage_Imap' );
Zend_Loader::loadClass ( 'Zend_Mail_Storage' );
Zend_Loader::loadClass ( 'Zend_Mail_Protocol_Exception' );

class GmailEmailConfirmer {
	
	public $mail;
	public $username;
	public $password;
	
	function __construct($username, $password) {
		
		echo ("<hr>Starting Gmail Email Confirmer. " . date ( "m-d-y h:i:s A" ) . " <br>");
		
		$this->username = $username;
		$this->password = $password;
		$this->connect ();
		
		$maxMessage = $this->getMessageCount ();
		echo ("There are $maxMessage emails.<br>");
		
		$folders = $this->getFolders();
		echo ("These are your folders:<br>");
		print_r($folders);
		echo("<br>");
		
		$this->confirmEmails (100);
	
	}
	
	function confirmEmails($matchLimit = 25) {
		try {
			foreach ( $this->mail as $messageId => $message ) {
				
				if ($message->hasFlag ( Zend_Mail_Storage::FLAG_SEEN )) {
					continue;
				}
				$body = $message->getContent ();
				
				/*** make sure there is an http:// on all URLs ***/
				$body = preg_replace ( "/([^\w\/])(www\.[a-z0-9\-]+\.[a-z0-9\-]+)/i", "$1http://$2", $body );
				$matchFound = false;
				preg_match ( '/http[^\s]+/', $body, $matches );
				$link = null;
				foreach ( $matches as $match ) {
					$end = stripos ( $match, '">' );
					if ($end !== false) {
						$match = substr ( $match, 0, strlen ( $match ) - $end );
					}
					if (stripos ( $match, "activate" ) || stripos ( $match, "confirm" ) || stripos ( $match, "verify" ) || stripos ( $match, "validate" )) {
						$link = $match;
						echo ("Match Found: $link<br>");
						$matchFound = true;
						break;
					}
				}
				
				// get the unique id of the current message before any operations
				$messageUniqueId = $this->mail->getUniqueId ( $messageId );
				
				// first get the message's current imap id
				$currentMessageId = $this->mail->getNumberByUniqueId ( $messageUniqueId );
				
				// If body contains confirm or activate link, perform click then delete message
				if ($matchFound) {
					// Click on link
					file_get_contents ( $link );
					
					// Mark it as seen
					$this->mail->setFlags ( $currentMessageId, array (Zend_Mail_Storage::FLAG_SEEN ) );
					
					// then move it to its destination
					$this->mail->moveMessage ( $currentMessageId, '[Gmail]/Trash' );
					
					$matchLimit --;
				
				/** 
				 * or remove it:
				 * $mail->removeMessage($currentMessageId);
				 */
				} else {
					// If message body has software lingo mark as read
					if (stripos ( $body, "pad" ) || stripos ( $body, "software" ) || stripos ( $body, "submission" ) || stripos ( $body, "submitted " )) {
						$this->mail->setFlags ( $currentMessageId, array (Zend_Mail_Storage::FLAG_SEEN ) );
					}
				}
				
				if ($matchLimit <= 0) {
					echo ("Match Limit Reached<br>");
					break;
				}
			}
		} catch ( Exception $e ) {
			echo ($e->getMessage () . "<br>");
			$this->connect ();
			$this->confirmEmails ( $matchLimit );
		}
	
	}
	
	function getMessageCount() {
		$messageCount = 0;
		try {
			$messageCount = $this->mail->countMessages ();
		} catch ( Exception $e ) {
			echo ($e->getMessage () . "<br>");
			$this->connect ( "Re-" );
			$messageCount = $this->getMessageCount ();
		}
		return $messageCount;
	}
	
	function getFolders() {
		$results = array ();
		$folders = new RecursiveIteratorIterator ( $this->mail->getFolders (), RecursiveIteratorIterator::SELF_FIRST );
		foreach ( $folders as $folder ) {
			//echo ("Folder: $folder<br>");
			$results [] = $folder->getGlobalName();
		}
		return $results;
	}
	
	function connect($prefix = null) {
		echo ($prefix . "Connecting To Email<br>");
		// connecting with Imap
		$this->mail = new Zend_Mail_Storage_Imap ( array ('host' => 'imap.gmail.com', 'user' => $this->username, 'password' => $this->password, 'ssl' => 'SSL' ) );
	
	}

}
//$obj = new GmailEmailConfirmer ( 'admin@chrisqueen.com', 'Neeuq011$' );
?>