<?php
$cache_expire = 60 * 60 * 24 * 365;
header("Pragma: public");
header("Cache-Control: max-age=" . $cache_expire);
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_expire) . ' GMT');
?>
<link rel="canonical" href="https://www.wepromotethis.com">
<link rel="canonical" href="https://www.wepromotethis.com/hop">
<link rel="canonical" href="https://www.facebook.com/pages/WePromoteThiscom/367519556648222">
<script src="//connect.facebook.net/en_US/all.js"></script>