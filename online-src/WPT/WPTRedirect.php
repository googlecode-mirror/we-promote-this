<?php

require_once 'CBUtils/CBAbstract.php';

//error_reporting ( E_ERROR );
// Errors only


error_reporting ( 0 );
// No errors


class WPTRedirect extends CBAbstract {
	
	public $fakeUsersMap;
	public $hop;
	
	function constructClass() {
		ob_clean ();
		if (isset ( $_REQUEST ["uid"] )) {
			$mainUser = $this->getUserById ( $_REQUEST ["uid"] );
		}
		if (isset ( $mainUser )) {
			if (isset ( $_REQUEST ['debug'] )) {
				echo ("Main User: $mainUser<br>");
			}
			$clickbankID = $this->returnUserAffiliateLink ( $mainUser );
		} else {
			if (isset ( $_REQUEST ['debug'] )) {
				echo ("Could not find main user<br>");
			}
		}
		if (! isset ( $clickbankID )) {
			$clickbankID = "cq2smooth";
		}
		if (isset ( $_REQUEST ['debug'] )) {
			echo ("ClickBank ID: $clickbankID<br>");
		}
		if (isset ( $_REQUEST ['pid'] )) {
			$productID = $_REQUEST ["pid"];
		}
		if (isset ( $_REQUEST ['tid'] )) {
			$trackingID = $_REQUEST ["tid"] . '_WPT';
		} else {
			$trackingID = 'WPT';
		}
		if (isset ( $productID )) {
			$hop = "http://" . $clickbankID . "." . $productID . ".hop.clickbank.net/";
		} else {
			$hop = "https://" . $clickbankID . ".accounts.clickbank.com/marketplace.htm";
		}
		if (isset ( $trackingID ) && strlen ( $trackingID ) > 0) {
			$hop .= "?tid=" . $trackingID;
		}
		
		if (isset ( $_REQUEST ["final"] )) {
			// If final is set then get the hop location from the cookie
			if (isset ( $_COOKIE ['hop'] )) {
				$this->hop = $_COOKIE ['hop'];
			} else {
				$this->hop = $hop;
			}
		} else if (isset ( $_REQUEST ["download"] )) {
			// Set cookie then change hop to share and get it url
			setcookie ( 'hop', $hop );
			$this->hop = "http://www.shareandgetit.com/process/process.php?data=YTo4OntzOjEwOiJmYW5wYWdldXJsIjtzOjYzOiJodHRwczovL3d3dy5mYWNlYm9vay5jb20vcGFnZXMvV2VQcm9tb3RlVGhpc2NvbS8zNjc1MTk1NTY2NDgyMjIiO3M6NDoicG9zdCI7czoxMjE6Ikp1c3QgZG93bmxvYWRlZCB0aGlzIGF3ZXNvbWUgcHJvZHVjdCBmb3IgZnJlZSBmcm9tIGh0dHA6Ly9XZVByb21vdGVUaGlzLmNvbSBAd2Vwcm9tb3RldGhpcyAuIENoZWNrIGl0IG91dCEgI3dlcHJvbW90ZXRoaXMiO3M6NDoiZmlsZSI7czo0MzoiaHR0cDovL3d3dy53ZXByb21vdGV0aGlzLmNvbS9zaGFyZS9wcm9kdWN0ICI7czo3OiJibG9nZ2VyIjtzOjEzOiJ3ZXByb21vdGV0aGlzIjtzOjY6ImRvbWFpbiI7czoxNDoiY2hyaXNxdWVlbi5jb20iO3M6NzoiYnRuUGFnZSI7czoyOToiL3dlLXByb21vdGUtdGhpcy8/cmVwZWF0PXczdGMiO3M6NzoiYnRubmFtZSI7czoyMToiR2V0IFlvdXIgRG93bmxvYWQgTm93IjtzOjg6Imxhbmd1YWdlIjtzOjU6ImVuLVVTIjt9";
		} else {
			$this->hop = $hop;
		}
	}
	
	function getUserById($userID) {
		$user = null;
		$query = "SELECT um.meta_value AS 'userLink', COALESCE(um5.user_id,1) AS 'upline', l.id AS 'level', um3.meta_value AS 'earning', COALESCE(um4.meta_value,false) AS 'pass' 
                FROM
                wp_2_pmpro_memberships_users AS mu 
                JOIN wp_2_pmpro_membership_levels AS l ON (l.id = mu.membership_id)
                JOIN wp_usermeta AS um ON (um.meta_key='clickbank' AND um.user_id=mu.user_id)
                LEFT JOIN wp_usermeta AS um2 ON (um2.meta_key='cb_referer' AND um2.user_id = um.user_id)
                LEFT JOIN wp_usermeta AS um5 ON (um5.meta_key='clickbank' AND um5.meta_value = um2.meta_value)
                LEFT JOIN wp_usermeta AS um3 ON (um3.meta_key='cbearnings' AND um3.user_id = um.user_id)
                LEFT JOIN wp_usermeta AS um4 ON (um4.meta_key='cb_pass' AND um4.user_id = um.user_id)
                WHERE
                mu.user_id = " . $userID . "
				LIMIT 1";
		$result = $this->getDBConnection ()->queryWP ( $query );
		if (count ( $result ) > 0) {
			$row = $result-> fetch_assoc();
			$userLink = $row ['userLink'];
			$upline = $row ["upline"];
			$level = $row ["level"];
			$earning = $row ["earning"];
			$pass = $row ["pass"];
			$user = new User ( $userID, $userLink, $upline, $level, $earning, $pass );
			if (! $user->isValid ()) {
				$user = null;
			}
		}
		return $user;
	}
	
	function getUserByLink($userLink) {
		$user = null;
		$query = "SELECT um.user_id AS 'userID', COALESCE(um2.meta_value,1) AS 'upline', l.id AS 'level', um3.meta_value AS 'earning', COALESCE(um4.meta_value,false) AS 'pass' 
				FROM 
				wp_2_pmpro_memberships_users AS mu 
                JOIN wp_2_pmpro_membership_levels AS l ON (l.id = mu.membership_id)
                JOIN wp_usermeta AS um ON (um.meta_key='clickbank' AND um.user_id=mu.user_id)
                LEFT JOIN wp_usermeta AS um2 ON (um2.meta_key='cb_referer' AND um2.user_id = um.user_id)
                LEFT JOIN wp_usermeta AS um5 ON (um5.meta_key='clickbank' AND um5.meta_value = um2.meta_value)
                LEFT JOIN wp_usermeta AS um3 ON (um3.meta_key='cbearnings' AND um3.user_id = um.user_id)
                LEFT JOIN wp_usermeta AS um4 ON (um4.meta_key='cb_pass' AND um4.user_id = um.user_id)
                WHERE
                um.meta_value = " . $userLink . "
				LIMIT 1";
		$result = $this->getDBConnection ()->queryWP ( $query );
		if (count ( $result ) > 0) {
			$row = $result-> fetch_assoc();
			$userID = $row ['userID'];
			$upline = $row ["upline"];
			$level = $row ["level"];
			$earning = $row ["earning"];
			$pass = $row ["pass"];
			$user = new User ( $userID, $userLink, $upline, $level, $earning, $pass );
			if (! $user->isValid ()) {
				$user = null;
			}
		}
		return $user;
	}
	
	function returnUserAffiliateLink(User $user) {
		$link = $user->link;
		//echo("Check link for: $user<br>");
		if (isset ( $user )) {
			$origUserPass = $user->pass;
			if ($user->getPass ()) {
				$user->setPass ( false );
				$newUser = $this->getUserById ( $user->upline );
				if (isset ( $_REQUEST ['debug'] )) {
					echo ("New User (is old): $newUser<br>");
				}
				if (isset ( $newUser ) && $newUser->id != $user->id) {
					if (isset ( $_REQUEST ['debug'] )) {
						echo ("New User (is new): $newUser<br>");
					}
					$link = $this->returnUserAffiliateLink ( $newUser );
				}
			} else {
				$user->setPass ( true );
			}
			// Save current state of user
			$updateQuery = "Update wp_usermeta SET meta_value='" . ($user->getPass () ? 1 : 0) . "' WHERE meta_key='cb_pass' AND user_id=" . $user->id;
			if (isset ( $_REQUEST ['debug'] )) {
				echo ("Update Query: $updateQuery<br>");
			}
			$this->getDBConnection ()->queryWP ( $updateQuery );
			$affected = $this->getDBConnection()->getWPDBConnection()->affected_rows;
			if (isset ( $_REQUEST ['debug'] )) {
				echo ("Affected : $affected<br>");
			}
			if ($affected == 0 && $user->getPass () != $origUserPass) {
				if (isset ( $_REQUEST ['debug'] )) {
					echo ("No rows affected after updating<br>");
				}
				$insertQuery = "INSERT INTO wp_usermeta (user_id,meta_key,meta_value) VALUES(" . $user->id . ",'cb_pass'," . ($user->getPass () ? 1 : 0) . ")";
				$this->getDBConnection ()->queryWP ( $insertQuery );
				if (isset ( $_REQUEST ['debug'] )) {
					echo ("Inset Query: $insertQuery<br>");
				}
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
	
	public function getMeta() {
		if (isset ( $this->hop ) && ! isset ( $_REQUEST ['debug'] )) {
			echo ('<meta content="5; url=' . $this->hop . '"');
		}
	}
	
	public function getRedirectJS() {
		if (isset ( $this->hop ) && ! isset ( $_REQUEST ['debug'] )) {
			echo ('
			<script type="text/javascript">
			window.location = "' . $this->hop . '";
			</script>');
		}
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
		$this->id = $i;
		$this->link = $li;
		$this->upline = $u;
		$this->level = $l;
		$this->earning = $e;
		$this->setPass ( $p );
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
		$pass = ($this->earning >= $maxEarning) ? true : $this->pass;
		return $pass;
	}
	
	public function setPass($p) {
		$this->pass = ( bool ) $p;
	}
	
	public function isValid() {
		return isset ( $this->id ) && isset ( $this->upline ) && $this->level > 0;
	}
	
	public function __toString() {
		return "User ID: " . $this->id . " | Upline: " . $this->upline . " | Pass: " . ( int ) ($this->pass) . " | Level: " . $this->level . " | Earnings: " . $this->earning;
	}
}

$wpe = new WPTRedirect ( );
?>
<html>
<head>
<?php
$wpe->getMeta ();
?>


<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-21491132-3']);
  _gaq.push(['_trackPageview']);

</script>

<script type="text/javascript"
	src="http://www.google-analytics.com/ga.js">
</script>
<?php
$wpe->getRedirectJS ();
?>
</head>
<body>
<h1 align="center">You are being redirected to: <a
	href="<?php
	echo $wpe->hop;
	?>"><?php
	echo $wpe->hop;
	?></a></h1>
<?php
ob_end_flush ();
?>
</body>
</html>
<?php
exit ( 0 );
?>