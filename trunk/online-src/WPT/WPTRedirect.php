<?php

ob_start();

// Use Word Press Settings and use that database
require_once 'CBUtils/CBAbstract.php';

error_reporting(E_ERROR);
// Errors only

require_once ("CBUtils/CBAbstract.php");

class WPTRedirect extends CBAbstract {

	public $fakeUsersMap;

	function constructClass() {
		ob_clean();
		if (isset($_REQUEST["uid"])) {
			$mainUser = $this -> getUserById($_REQUEST["uid"]);
		}
		if (isset($mainUser)) {
			if (isset($_REQUEST['debug'])) {
				echo("Main User: $mainUser<br>");
			}
			$clickbankID = $this -> returnUserAffiliateLink($mainUser);
		}else{
			if (isset($_REQUEST['debug'])) {
				echo("Could not find main user<br>");
			}
		}
		if (!isset($clickbankID)) {
			//$clickbankID = "cq2smooth";
			$clickbankID = "cq2smooth_test";
		}
		if (isset($_REQUEST['debug'])) {
			echo("ClickBank ID: $clickbankID<br>");
		}
		if (isset($_REQUEST['pid'])) {
			$productID = $_REQUEST["pid"];
		}
		if (isset($_REQUEST['tid'])) {
			$trackingID = $_REQUEST["tid"] . '_WPT';
		} else {
			$trackingID = 'WPT';
		}
		if (isset($productID)) {
			$hop = "http://" . $clickbankID . "." . $productID . ".hop.clickbank.net/";
		} else {
			$hop = "https://" . $clickbankID . ".accounts.clickbank.com/marketplace.htm";
		}
		if (isset($trackingID) && strlen($trackingID) > 0) {
			$hop .= "?tid=" . $trackingID;
		}
		if (isset($_REQUEST['debug'])) {
			echo("Hop to $hop");
			die();
		} else {
			header('Location: ' . $hop);
		}

		// Follow with GOOGLE Analytics Code
	}

	function getUserById($userID) {
		$user = null;
		$query = "SELECT um.meta_value AS 'userLink', COALESCE(um2.user_id,1) AS 'upline', l.id AS 'level', um3.meta_value AS 'earning', COALESCE(um4.meta_value,false) AS 'pass' 
				FROM 
				wp_2_pmpro_membership_levels AS l
				JOIN wp_2_pmpro_memberships_users AS mu ON (l.id = mu.membership_id)
				JOIN wp_usermeta AS um ON (um.meta_key='clickbank' AND mu.user_id = ".$userID.")
				LEFT JOIN wp_usermeta AS um2 ON (um2.meta_key='cb_referer' AND um2.user_id = um.user_id)
				LEFT JOIN wp_usermeta AS um3 ON (um3.meta_key='cbearnings' AND um3.user_id = um.user_id)
				LEFT JOIN wp_usermeta AS um4 ON (um4.meta_key='cb_pass' AND um4.user_id = um.user_id)
				LIMIT 1";
		$result = $this -> getDBConnection() -> queryWP($query);
		if (count($result) > 0) {
			$row = mysql_fetch_assoc($result);
			$userLink = $row['userLink'];
			$upline = $row["upline"];
			$level = $row["level"];
			$earning = $row["earning"];
			$pass = $row["pass"];
			$user = new User($userID, $userLink, $upline, $level, $earning, $pass);
			if (!$user -> isValid()) {
				$user = null;
			}
		}
		return $user;
	}

	function getUserByLink($userLink) {
		$user = null;
		$query = "SELECT um.user_id AS 'userID', COALESCE(um2.meta_value,'cq2smooth') AS 'upline', l.id AS 'level', um3.meta_value AS 'earning', COALESCE(um4.meta_value,false) AS 'pass' 
				FROM 
				wp_11_pmpro_membership_levels AS l
				JOIN wp_11_pmpro_memberships_users AS mu ON (l.id = mu.membership_id)
				JOIN wp_usermeta AS um ON (um.meta_key='clickbank' AND um.meta_value='" . $userLink . "' AND mu.user_id = um.user_id)
				LEFT JOIN wp_usermeta AS um2 ON (um2.meta_key='cb_referer' AND um2.user_id = um.user_id)
				LEFT JOIN wp_usermeta AS um3 ON (um3.meta_key='cbearnings' AND um3.user_id = um.user_id)
				LEFT JOIN wp_usermeta AS um4 ON (um4.meta_key='cb_pass' AND um4.user_id = um.user_id)
				LIMIT 1";
		$result = $this -> getDBConnection() -> queryWP($query);
		if (count($result) > 0) {
			$row = mysql_fetch_assoc($result);
			$userID = $row['userID'];
			$upline = $row["upline"];
			$level = $row["level"];
			$earning = $row["earning"];
			$pass = $row["pass"];
			$user = new User($userID, $userLink, $upline, $level, $earning, $pass);
			if (!$user -> isValid()) {
				$user = null;
			}
		}
		return $user;
	}

	function returnUserAffiliateLink(User $user) {
		$link = $user -> link;
		//echo("Check link for: $user<br>");
		if (isset($user)) {
			$origUserPass = $user -> pass;
			if ($user -> getPass()) {
				$user -> setPass(false);
				$newUser = $this -> getUserById($user -> upline);
				//echo ("New User (is old): $newUser<br>");
				if (isset($newUser) && $newUser -> id != $user -> id) {
					//echo ("New User (is new): $newUser<br>");
					$link = $this -> returnUserAffiliateLink($newUser);
				}
			} else {
				$user -> setPass(true);
			}
			// Save current state of user
			$updateQuery = "Update wp_usermeta SET meta_value=" . ($user -> getPass() ? 1 : 0) . " WHERE meta_key='cb_pass' AND user_id=" . $user -> id;
			//echo ("Update Query: $updateQuery<br>");
			mysql_query($updateQuery);
			$affected = mysql_affected_rows();
			if ($affected == 0 && $user -> getPass() != $origUserPass) {
				//echo ("No rows affected<br>");
				$insertQuery = "INSERT INTO wp_usermeta (user_id,meta_key,meta_value) VALUES(" . $user -> id . ",'cb_pass'," . ($user -> getPass() ? 1 : 0) . ")";
				mysql_query($insertQuery);
				//echo ("Inset Query: $insertQuery<br>");
			}
		}
		return $link;
	}

	function __destruct() {
		//parent::__destruct();
		/*
		 $logFile = get_class ( $this ) . "_logfile.html";
		 $f = fopen ( $logFile, "w" );
		 fwrite ( $f, $this->getOutputContent() );
		 fclose ( $f );
		 exec ( "start " . $logFile );
		 */
	}

}

class User {
	public $id;
	public $link;
	public $upline;
	public $pass;
	public $level;
	public $earning;

	public function User($i, $li = "cq2smooth", $u = 1, $l = 0, $e, $p = false) {
		$this -> id = $i;
		$this -> link = $li;
		$this -> upline = $u;
		$this -> level = $l;
		$this -> earning = $e;
		$this -> pass = $p;
	}

	public function getPass() {
		$maxEarning = 0;
		switch ($this->level) {
			case 1 :
				$maxEarning = 10;
				break;
			case 2 :
				$maxEarning = 20;
				break;
			case 3 :
				$maxEarning = 40;
				break;
			case 4 :
				$maxEarning = 100;
				break;
			case 5 :
				$maxEarning = 250;
				break;
			case 6 :
				$maxEarning = 10000000000;
				break;
		}
		$pass = ($this -> earning >= $maxEarning) ? true : $this -> pass;
		return $pass;
	}

	public function setPass($p) {
		$this -> pass = $p;
	}

	public function isValid() {
		return isset($this -> id) && isset($this -> upline) && $this -> level > 0;
	}

	public function __toString() {
		return "User ID: " . $this -> id . " | Upline: " . $this -> upline . " | Pass: " . ( int )($this -> pass) . " | Level: " . $this -> level;
	}

}

$wpe = new WPTRedirect();
?>
<script type="text/javascript">
	var _gaq = _gaq || [];
	_gaq.push(['_setAccount', 'UA-21491132-3']);
	_gaq.push(['_trackPageview']);

	(function() {
		var ga = document.createElement('script');
		ga.type = 'text/javascript';
		ga.async = true;
		ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
		var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(ga, s);
	})();

</script>

<?php
ob_end_flush ();
die ()?>
