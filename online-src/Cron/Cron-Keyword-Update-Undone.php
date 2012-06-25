<?php
require_once 'CronAbstract.php';
class CronKeywordUpdateUndone extends CronAbstract
{
    function runCron ()
    {
        $file = "CBKeywordExtractor.txt";
        $this->getCommandLineHelper()->run_in_background("CBKeywordExtractor.php update-undone", $file);
        $url = "http://" . RemoteHost . RemoteHostRootFolder . "CB/CBUtils/ShowLog.php?log=$file";
        echo ("View Results <a href='$url'>$url</a>");
    }
}
new CronKeywordUpdateUndone();
?>