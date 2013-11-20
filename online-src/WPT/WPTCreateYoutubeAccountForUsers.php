<?php
require_once ("CBUtils/CBAbstract.php");
require_once 'Video/User.php';
class WPTCreateYoutubeAccountForUsers extends CBAbstract {
	function __construct() {
		parent::__construct ();
	}
	function __destruct() {
		parent::__destruct ();
	}
	function constructClass() {
		
		// Check for submissions of youtube account credentials
		if (isset ( $_REQUEST ['user_wp_id'] ) && isset ( $_REQUEST ['email'] ) && isset ( $_REQUEST ['password'] )) {
			$user = new User ( );
			$user->user_id = $_REQUEST ['email'];
			$user->user_password = $_REQUEST ['password'];
			$user->user_wp_id = $_REQUEST ['user_wp_id'];

			$accountQuery = "Select meta_key as account FROM wp_usermeta where user_id = " . $user->user_wp_id . " AND meta_key like 'youtube%_password' ORDER BY umeta_id DESC limit 1";
			$aresult = $this->getDBConnection ()->queryWP ( $accountQuery );
			$arow = $aresult->fetch_assoc ();
			$account = $arow ["account"];
			$account = str_ireplace ( 'youtube', '', $account );
			$account = str_ireplace ( '_password', '', $account );
			$numAccount = (( int ) $account) + 1;
			$insertQuery = "Insert into wp_usermeta (user_id, meta_key,meta_value) VALUES(" . $user->user_wp_id . ",'youtube" . $numAccount . "','" . $user->user_id . "'),(" . $user->user_wp_id . ",'youtube" . $numAccount . "_password','" . $user->user_password . "')";
			// echo ("Insert Query:$insertQuery<br>");
			$this->getDBConnection ()->queryWP ( $insertQuery );
			
			echo ("Youtube Channel Added Successfully<br>");
		}
		
		// Select a random user that needs a channel created
		//  TODO: Change so the query gets the main youtube account and password
		

		$query = "Select u1.meta_value AS 'user_id', u2.meta_value AS 'user_password', u1.user_id AS 'user_wp_id' FROM wp_usermeta AS u1
		JOIN wp_usermeta AS u2 ON (u1.user_id = u2.user_id)
		JOIN wp_usermeta AS u3 ON (u2.user_id = u3.user_id)
		JOIN wp_usermeta AS u4 ON (u3.user_id = u4.user_id)
		
		WHERE u1.meta_key LIKE 'youtube%' AND u1.meta_value IS NOT NULL
		AND u2.meta_key=CONCAT(u1.meta_key,'_password') AND u2.meta_value IS NOT NULL
		AND u3.meta_key='clickbank' AND u3.meta_value IS NOT NULL
		AND u4.meta_key='clickbank_clerk_api_key' AND u4.meta_value IS NOT NULL";
		$result = $this->getDBConnection ()->queryWP ( $query );
		$users = array ();
		while ( ($row = $result->fetch_assoc ()) ) {
			
			//TODO: Remove this hardcoded entries when the query is updated
			$row ['user_id'] = 'tbyum07@gmail.com';
			$row ['user_password'] = 'Neeuq011!$';
			//$row['user_wp_id'] = '3';
			

			$user = new User ( );
			$user->user_id = $row ['user_id'];
			$user->user_password = $row ['user_password'];
			$user->user_wp_id = $row ['user_wp_id'];
			$users [$row ['user_wp_id']] = $user;
			//echo("Adding user: $user<br>");
		}
		
		// TODO: Use another query to determine what user has the least amount of channels
		

		// Get user channels ordered by the least amount of youtube channels
		$orderedUsersQuery = "SELECT user_wp_id FROM users WHERE user_wp_id IN (" . implode ( ',', array_keys ( $users ) ) . ") GROUP BY user_wp_id ORDER BY COUNT(user_id) ASC";
		//echo("Ordered Useres Query: ".$orderedUsersQuery."<br>");
		$result = $this->runQuery ( $orderedUsersQuery, $this->getDBConnection ()->getDBConnection () );
		$usersOrderedByChannelCountAsc = array ();
		while ( ($row = $result->fetch_assoc ()) ) {
			if (isset ( $users [$row ['user_wp_id']] )) {
				$usersOrderedByChannelCountAsc [] = $users [$row ['user_wp_id']];
			}
		}
		//echo('Users Ordered By Channel Count Asc: '.print_r($usersOrderedByChannelCountAsc,true));
		

		// Found out if there are any users that dont have any channels
		$usersWithoutChannels = array_diff ( $users, $usersOrderedByChannelCountAsc );
		
		if (count ( $usersWithoutChannels ) > 0) {
			// If there are users withou channels pick a random user from the group
			$user = $usersWithoutChannels [array_rand ( $usersWithoutChannels )];
			echo ( 'Users Without Channels: ' . print_r ( $usersWithoutChannels, true ) );
		} else {
			// Otherwise pick the user with the least amount of channels
			$user = array_shift ( $usersOrderedByChannelCountAsc );
			echo ( 'User ID  with least amount of Channels: ' . $user->user_wp_id );
		
		}
		
		echo ('
		<html>
		<body>
		<form id="wptyoutubechannelcreation" name="" action="WPTCreateYoutubeAccountForUsers.php" method="POST">
		<input type="text" id="new_channel_id" name="new_channel_id" value="WPT_' . date ( 'Y_m_d_h_i_s' ) . '" disabled ><br>
		<input type="text" id="user_id" name="user_id" value="' . $user->user_id . '" disabled ><br>
		<input type="text" id="user_password" name="user_password" value="' . $user->user_password . '" disabled ><br>
		<input type="hidden" id="user_wp_id" name="user_wp_id" value="' . $user->user_wp_id . '"><br>
		');
		
		// Display fields for new youtube account creation
		echo ('
		<input type="text" id="email" name="email"><br>
		<input type="text" id="password" name="password"><br>
		<input type="submit" value="Submit"><br>
		');
		
		// Close html tags
		echo ('
		</form>
		</body>
		</html>');
	
	}

}
$wcyafu = new WPTCreateYoutubeAccountForUsers ( );
?>