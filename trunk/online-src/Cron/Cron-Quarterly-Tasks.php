<?php
require_once 'Cron-Clear-Tasks.php';
require_once 'CronController.php';
new CronController("WPTUploadVideoToHost");
require_once 'Cron-Keyword-Update-Undone.php';
?>