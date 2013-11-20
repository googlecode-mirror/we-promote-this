<?php
require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/wp-load.php');
require_once(dirname(__FILE__) . '/EpiCurl.php' );
require_once(dirname(__FILE__) . '/EpiOAuth.php' );
require_once(dirname(__FILE__) . '/EpiTwitter.php' );
require_once(dirname(dirname(__FILE__)) . '/utils.php' );

$consumer_key = get_option('social_connect_twitter_consumer_key');
$consumer_secret = get_option('social_connect_twitter_consumer_secret');
$twitter_api = new EpiTwitter($consumer_key, $consumer_secret);

$twitter_api->setToken($_GET['oauth_token']);
$token = $twitter_api->getAccessToken();
$twitter_api->setToken($token->oauth_token, $token->oauth_token_secret);

$user = $twitter_api->get_accountVerify_credentials();
$name = $user->name;
$screen_name = $user->screen_name;
$twitter_id = $user->id;

//$user_str = serialize($user);
//preg_match("/user_id=([0-9]*)/", $user_str, $match_id);
//$twitter_id=$match_id[1];
//preg_match("/screen_name=([a-zA-Z0-9_]{1,15}&?)/", $user_str, $match_user);
//$screen_name=$match_user[1];

$signature = social_connect_generate_signature($twitter_id);
?>

<html>
<head>
<script>
function init() {
  window.opener.wp_social_connect({'action' : 'social_connect', 'social_connect_provider' : 'twitter', 
    'social_connect_signature' : '<?php echo $signature ?>',
    'social_connect_twitter_identity' : '<?php echo $twitter_id ?>',
    'social_connect_screen_name' : '<?php echo $screen_name ?>',
    'social_connect_name' : '<?php echo $name ?>'});
    
  window.close();
}
</script>
</head>
<body onload="init();">
</body>
</html>
