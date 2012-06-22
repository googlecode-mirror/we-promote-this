<?php

/*
video.create
Create the metadata for video 65535.
*/

include('xmlrpc-2_1/lib/xmlrpc.inc');
include('class.RevverAPI.php');

$api = new RevverAPI('https://api.staging.revver.com/xml/1.0?login=revtester&passwd=testacct');

$id = 65535;
$title = 'My New Parrot';
$keywords = array('parrot', 'pet');
$ageRestriction = 1;
$options = array('url' => 'arrvideos.example.com', 'author' => 'Billy Doyle');

$results = $api->callRemote('video.create', $id, $title, $keywords, $ageRestriction, $options);

echo '<pre>';
var_dump($results);
echo '</pre>';

?>