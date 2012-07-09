<?php
require_once ("CBUtils/CBAbstract.php");
require_once ('Account/YoutubeAccount.php');

class WPTCreateYoutubeAccountForUsers extends CBAbstract {
	
	function __construct() {
		parent::__construct ();
	}
	
	function __destruct() {
		parent::__destruct ();
	}
	
	function constructClass() {
		$this->handleARGV ();
	}
	
	function handleARGV() {
		global $argv;
		if (! isset ( $argv ) || count ( $argv ) <= 1) {
			$this->createYTAccountForUsers ();
		} else {
			array_shift ( $argv );
			foreach ( $argv as $value ) {
				$keyArray = split ( "=", $value );
				$key = $keyArray [0];
				$keyValue = $keyArray [1];
				switch ($key) {
					case "uid" :
						$uid = $keyValue;
						if (isset ( $uid )) {
							$this->createYTAccountFor ( $uid );
						}
						break;
				}
			}
		}
	}
	
	function createYTAccountForUsers() {
		// For each user create one new YT account
		echo ("<hr>Start Date/Time: " . date ( "m/d/Y h:i:s A" ) . "<br>");
		$query = "SELECT um.user_id as uid, l.id as level ,um3.meta_value AS earned FROM
		 		wp_2_pmpro_memberships_users AS mu 
                JOIN wp_2_pmpro_membership_levels AS l ON (l.id = mu.membership_id)
                JOIN wp_usermeta AS um ON (um.meta_key='clickbank' AND um.user_id=mu.user_id)
                LEFT JOIN wp_usermeta AS um2 ON (um2.meta_key='clickbank_clerk_api_key' AND um2.user_id=mu.user_id)
                LEFT JOIN wp_usermeta AS um3 ON (um3.meta_key='cbearnings' AND um3.user_id = um.user_id)
				WHERE CHAR_LENGTH(um.meta_value)>0 and CHAR_LENGTH(um3.meta_value)>0";
		//echo ("Query: $query<br><br>");
		$className = get_class ( $this );
		$file = $className . ".txt";
		$result = $this->getDBConnection ()->queryWP ( $query );
		while ( ($row = mysql_fetch_assoc ( $result )) ) {
			$uid = $row ["uid"];
			$level = $row ["level"];
			$earned = $row ["earned"];
			// find out how mand account the user already has
			$accountsQuery = "Select count(*) as accounts FROM users where user_wp_id = $uid";
			$aresult = mysql_query ( $accountsQuery );
			$arow = mysql_fetch_assoc ( $aresult );
			$numAccount = $arow ["accounts"];
			
			// Override level for user id 1 (cq2smooth)
			if ($uid == 1) {
				$level = 6;
			}
			
			// Define max limits
			$maxEarning = 0;
			$maxAccounts = 0;
			switch ($level) {
				case 1 :
					$maxEarning = 10;
					$maxAccounts = 20;
					break;
				case 2 :
					$maxEarning = 20;
					$maxAccounts = 30;
					break;
				case 3 :
					$maxEarning = 40;
					$maxAccounts = 35;
					break;
				case 4 :
					$maxEarning = 100;
					$maxAccounts = 40;
					break;
				case 5 :
					$maxEarning = 250;
					$maxAccounts = 45;
					break;
				case 6 :
					$maxEarning = 10000000000;
					$maxAccounts = 50;
					break;
			}
			$pass = ($earned >= $maxEarning) ? false : true;
			$pass = ($numAccount >= $maxAccounts) ? false : $pass;
			if ($pass) {
				$cmd = $className . ".php uid=$uid";
				$this->getCommandLineHelper ()->run_in_background ( $cmd, $file );
			}
		}
	}
	
	function createYTAccountFor($uid) {
		echo ("Creating YT Account for User ID($uid) at " . date ( "m-d-y h:i:s A" ) . "<br>");
		$yt = new YoutubeAccount ( $this->getDBConnection ()->getDBConnection () );
		// Find name that doesnt already exist
		$validUserName = false;
		do {
			$username = "wptAAcq" . rand ( 1000, 40000 );
			$userEntry = $yt->getService ()->retrieveUser ( $username );
			if (! isset ( $userEntry )) {
				$validUserName = true;
			}
		} while ( ! $validUserName );
		$password = 'Tpw2012' . rand ( 0, 1000 ) . '$';
		$yt->create ( $username, $password );
		if ($yt->isValid ()) {
			echo ("Created YT Account: " . $yt->userName . "<br>");
			// Insert new yt account into wordpress database
			$aresult = $this->getDBConnection ()->queryWP ( "LOCK TABLES wp_usermeta WRITE" );
			$accountQuery = "Select meta_key as account FROM wp_usermeta where user_id = $uid AND meta_key like 'youtube%_password' ORDER BY umeta_id DESC limit 1";
			$aresult = $this->getDBConnection ()->queryWP ( $accountQuery );
			$arow = mysql_fetch_assoc ( $aresult );
			$account = $arow ["account"];
			$account = str_ireplace ( 'youtube', '', $account );
			$account = str_ireplace ( '_password', '', $account );
			$numAccount = (( int ) $account) + 1;
			$insertQuery = "Insert into wp_usermeta (user_id, meta_key,meta_value) VALUES($uid,'youtube" . $numAccount . "','" . $yt->userName . "'),($uid,'youtube" . $numAccount . "_password','" . $yt->password . "')";
			//echo ("Insert Query:$insertQuery<br>");
			$this->getDBConnection ()->queryWP ( $insertQuery );
			$aresult = $this->getDBConnection ()->queryWP ( "UNLOCK TABLES" );
		} else {
			echo ("Could not create a valid YT account<br>");
		
		}
	}
}
$wcyafu = new WPTCreateYoutubeAccountForUsers ( );
?>