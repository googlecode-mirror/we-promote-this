<?php
require_once 'Cron-Clear-Logs.php'; //Clean up all logs on mondays
require_once 'CronController.php';
require_once 'Cron-Update-WPT-User-Earnings.php';
new CronController ( "WPTCleanOldVideos" );
new CronController("WPTUploadScheduler");
//new CronController ( "WPTCreateYoutubeAccountForUsers" );
new CronController ( "CBMarketPlace" ); // This one actually has a check in place to only run weekly
require_once 'Cron-Keyword-Update-Random.php';
require_once 'Cron-Keyword-RunUpdateQuery.php';

?>