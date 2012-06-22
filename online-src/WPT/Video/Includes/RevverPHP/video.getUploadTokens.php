<?php

/*
video.getUploadTokens
Return 1 upload token for a new video to be owned by blackbeard.
*/

include('xmlrpc-2_1/lib/xmlrpc.inc');
include('class.RevverAPI.php');

$api = new RevverAPI('https://api.staging.revver.com/xml/1.0?login=revtester&passwd=testacct');

$count = 1;
$options = array('owner' => 'blackbeard');

$results = $api->callRemote('video.getUploadTokens', $count, $options);

echo '<pre>';
var_dump($results);
echo '</pre>';

?>
