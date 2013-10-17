<?php
require_once 'CronAbstract.php';
class CronKeywordUpdateRandom extends CronAbstract
{
    function runCron ()
    {
        $file = "CBKeywordExtractor.txt";
        $this->getCommandLineHelper()->run_in_background("CBKeywordExtractor.php update-random", $file);
        $url = "../WPT/CBUtils/ShowLog.php?log=$file";
		echo ("View <a href='$url'>$file </a> Log");
    }
}
new CronKeywordUpdateRandom();
?>