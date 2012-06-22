<?php

class YoutubeVideoResponseMatrix extends VideoUploader {
	
	//<yt:accessControl action='comment' permission='allowed'/>
	

	function acceptAllContacts($userName) {
		$yt = new Zend_Gdata_YouTube();
		$contactsFeed = $yt -> getContactFeed($userName);

		foreach ($contactsFeed as $contactsEntry) {
			$contactEntry -> setStatus($yt -> newStatus('accepted'));
			$contactEntry -> save();
		}
	}

}
?>