<?php

require_once ("CBUtils/CBAbstract.php");

class WPTCleanOldVideos extends CBAbstract {

    public $today;
    public $deleteVideos;
    public $uniqueVideosArray;
    public $totalVideos;

    function constructClass() {

        echo("Starting to  Cleaning Videos at " . date("m/d/Y h:i:s A") . "<br>");

        $this -> deleteVideos = array();
        $this -> uniqueVideosArray = array();
        $this -> totalVideos = 0;
        $this -> today = strtotime(date("Y-m-d h:i:s A"));
        $videoPath = "./" . RemoteServerVideoLocation;
        //echo("Video Path: $videoPath<br>");

        if (($dh = opendir($videoPath))) {
            while (false !== ($dat = readdir($dh))) {
                if ($dat != "." && $dat != ".." && $dat != ".svn" && $dat != "tmp") {
                    $path = $videoPath . $dat;
                    if (is_dir($path)) {
                        $this -> cleanOldVideos($path);
                        $this -> uniqueVideosArray[$dat] = 1;
                    }
                }
            }
            closedir($dh);
        }

        echo("There are " . count(array_keys($this -> uniqueVideosArray)) . " Unique Videos.<br>");
        echo("There are " . $this -> totalVideos . " Total Videos.<br>");
        echo("Deleting the following videos:<br>" . implode("<br>", array_keys($this -> deleteVideos)) . "<br>");
        $this -> deleteVideosInArray($this -> deleteVideos);
        echo("Finished Cleaning Videos at " . date("m/d/Y h:i:s A") . "<br><hr>");

    }

    function __destruct() {
        parent::__destruct();
    }

    function deleteVideosInArray(array $videos) {
        foreach ($videos as $video) {
            if (strlen($video) > 0 && file_exists($video)) {
                @unlink($video);
            }
        }
    }

    function cleanOldVideos($path) {
        if (is_file($path)) {
            // Check file for date
            $fileCreationTime = strtotime(date("Y-m-d h:i:s A", filemtime($path)));
            $daysDiff = floor(abs($this -> today - $fileCreationTime) / 60 / 60 / 24);
            //printf("There has been %d day(s) since $path was created.<br>", $daysDiff);
            $this -> totalVideos++;
            if ($daysDiff >= 1) {
                //echo("<b>$path is too old. Needs to be deleted</b><br><br>");
                $this -> deleteVideos[basename($path)] = $path;
            }
        } else if (is_dir($path)) {
            array_map(array($this, 'cleanOldVideos'), glob($path . '/*'));
            if ($this -> is_empty_folder($path)) {
                $this -> rrmdir($path);
            }
        } else {
            echo($path . " is not a file nor folder.<br>");
        }
    }

    private function is_empty_folder($dirname) {
        // Returns true if  $dirname is a directory and it is empty
        $result = false;
        // Assume it is not a directory
        if (is_dir($dirname)) {
            $result = true;
            // It is a directory
            $handle = opendir($dirname);
            while (($name = readdir($handle)) !== false) {
                if ($name != "." && $name != ".." && $name != ".svn") {
                    $result = false;
                    // directory not empty
                    break;
                    // no need to test more
                }
            }
            closedir($handle);
        }
        return $result;
    }

    private function rrmdir($path) {
        return is_file($path) ? @unlink($path) : array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path);
    }

}

new WPTCleanOldVideos();
?>