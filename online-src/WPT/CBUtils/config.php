<?
// This simple PHP / Mysql membership script was created by www.funkyvision.co.uk
// You are free to use this script at your own risk
// Please visit our website for more updates..
ob_start ();
session_start ();

require_once 'CBAbstract.php';

class Config extends CBAbstract {
	function constructClass() {
	}

}

new Config();
?>