<?php
require_once 'CronController.php';
require_once 'Cron-Update-WPT-User-Earnings.php';
new CronController("WPTCleanOldVideos");
new CronController("CBMarketPlace"); // This one actually has a check in place to only run weekly

?>