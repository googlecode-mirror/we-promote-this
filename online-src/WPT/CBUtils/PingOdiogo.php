<?php
require_once 'CBAbstract.php';
class PingOdiogo extends CBAbstract
{
    function constructClass ()
    {
        // PING odiogo.com to notify of new feed
        $FID = odiogofeedid;
        $response = $this->get_web_page("http://rpc.odiogo.com/ping/ping.php?method_name=weblogUpdates.extendedPing&feed_id=$FID");
        $response2 = $this->get_web_page("http://rpc.odiogo.com/ping/ping.php?method_name=weblogUpdates.ping&feed_id=$FID");
        echo ("Response: " . print_r($response['content']) . "<br>Response2: " . print_r($response2['content']));
    }
    function get_web_page ($url)
    {
        $options = array(CURLOPT_RETURNTRANSFER => true , // return web page
CURLOPT_HEADER => false , // don't return headers
CURLOPT_FOLLOWLOCATION => true , // follow redirects
CURLOPT_ENCODING => "" , // handle all encodings
CURLOPT_USERAGENT => "spider" , // who am i
CURLOPT_AUTOREFERER => true , // set referer on redirect
CURLOPT_CONNECTTIMEOUT => 120 , // timeout on connect
CURLOPT_TIMEOUT => 120 , // timeout on response
CURLOPT_MAXREDIRS => 10); // stop after 10 redirects
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $content = curl_exec($ch);
        $err = curl_errno($ch);
        $errmsg = curl_error($ch);
        $header = curl_getinfo($ch);
        curl_close($ch);
        $header['errno'] = $err;
        $header['errmsg'] = $errmsg;
        $header['content'] = $content;
        return $header;
    }
}
$obj = new PingOdiogo();
?>