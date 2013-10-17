<?php
require_once 'Cron-Clear-Tasks.php';
require_once 'CronController.php';
new CronController("WPTDaemon");
new CronController("WPTUploadVideoToHost");
new CronController("WPTDeleteBadVideos");
//require_once 'Cron-Keyword-Update-Undone.php';
?>