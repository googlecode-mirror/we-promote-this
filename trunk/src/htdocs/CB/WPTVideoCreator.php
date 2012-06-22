<?php
//ob_start ();
require_once 'CBUtils/CBAbstract.php';
require_once 'CBUtils/Includes/audioinfo.class.php';
require_once 'WPTVideoUploader.php';

class WPTVideoCreator extends CBAbstract {
	public $sw;
	public $sh;
	public $spt;
	public $audioLength;
	public $imageCount;
	public $titleEnd;
	public $font;
	public $borderHeight;
	public $root;

	function constructClass() {
		// Set the enviroment variable for GD
		$this->root = $this->getShortPathName(dirname(__FILE__));
		putenv('GDFONTPATH=' . $this->root . "/CBUtils/Includes/fonts");
		$this -> font = getenv("GDFONTPATH") . '/ARLRDBD.ttf';
		$this -> borderHeight = 100;
	}

	function getShortPathName($file) {
		$cmd = 'FOR %A IN ("' . $file . '") DO ECHO.%~sfA';
		exec($cmd, $output);
		return $output[2];
	}

	function createVideoFor($pid) {
		$query = "SELECT p.title, p.description FROM products as p WHERE p.id='$pid'";
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		$title = $row['title'];
		$description = $row['description'];
		$staus = "Nothing  Done";
		if ($title === false || $description === false || empty($title) || empty($description)) {
			$this -> getLogger() -> log("Title or description is empty for pid: $pid - Or they are not english.", PEAR_LOG_WARNING);
			echo("Cant create video for $pid. No Title or no description");
			$staus = "Cant Create video fro $pid: No Title or no description";
		} else {
			$link = "http://WePromoteThis.com/CB/" . $pid;
			$mp3 = $this -> getRandomAudio();
			$status = $this -> createVideo($pid, $title, $description, $link, $mp3);
			echo("Video for $pid Status = (" . $status . ")  at " . date("m/d/Y h:i:s A") . "<br>");
		}
		return $status;
	}

	function __destruct() {
		parent::__destruct();
	}

	function writeToFile($file, $txt, $mode = "w") {
		if (strcmp($mode, "a") == 0) {
			$txt .= "\r\n";
		}
		$fp = fopen($file, $mode);
		fwrite($fp, $txt);
		fclose($fp);
	}

	function createSlide($imagePath, $link) {
		$slide = imagecreatefromjpeg($imagePath);
		$width = imagesx($slide);
		$height = imagesy($slide);
		$white = imagecolorallocate($slide, 255, 255, 255);
		$blue = imagecolorallocate($slide, 0, 0, 255);
		// Add white border on top and bottom
		imagefilledrectangle($slide, 0, 0, $width, $this -> borderHeight, $white);
		imagefilledrectangle($slide, 0, $height - $this -> borderHeight, $width, $height, $white);
		$linkTxt = "Find Out More Now:/n" . $link;
		$yStart = (($height - $this -> borderHeight) + $height) / 2;
		$this -> addCenteredText($slide, $linkTxt, $this -> font, 24, $blue, $yStart);
		return $slide;
	}

	function addTitleToSlide($imgRes, $title) {
		$width = imagesx($imgRes);
		//$height = imagesy ( $imgRes );
		$maxTitleWidth = $width * .75;
		$titleFontSize = 24;
		$titleBbox = imagettfbbox($titleFontSize, 0, $this -> font, $title);
		$titleHeight = abs($titleBbox[1] - $titleBbox[7]);
		$titleY = ($titleHeight * (3 / 2)) + $this -> borderHeight;
		$red = imagecolorallocate($imgRes, 255, 0, 0);
		$txt = "";
		foreach (explode ( " ", $title ) as $word) {
			$txtBbox = imagettfbbox($titleFontSize, 0, $this -> font, $txt . $word);
			$txtWidth = abs($txtBbox[0] - $txtBbox[2]);
			$titleX = ($width - $txtWidth) / 2;
			if ($txtWidth <= $maxTitleWidth) {
				$txt .= $word . " ";
			} else {
				$txt = trim($txt);
				$txtBbox = imagettfbbox($titleFontSize, 0, $this -> font, $txt);
				$txtWidth = abs($txtBbox[0] - $txtBbox[2]);
				$titleX = ($width - $txtWidth) / 2;
				imagettftext($imgRes, $titleFontSize, 0, $titleX, $titleY, $red, $this -> font, $txt);
				$txt = $word . " ";
				$titleY += ($titleHeight * 3 / 2);
			}
		}
		$txt = trim($txt);
		$txtBbox = imagettfbbox($titleFontSize, 0, $this -> font, $txt);
		$txtWidth = abs($txtBbox[0] - $txtBbox[2]);
		$this -> titleEnd = $txtBbox[1];
		$titleX = ($width - $txtWidth) / 2;
		imagettftext($imgRes, $titleFontSize, 0, $titleX, $titleY, $red, $this -> font, $txt);
	}

	function createVideoImages($pid, $title, $description, $link) {
		str_ireplace("..", ".", $description);
		$outputPath = $this->root . "/" . LocalServerVideoLocation . $pid . "/images/";
		if (!file_exists($outputPath)) {
			mkdir($outputPath, 0777, true);
		}
		$imagecount = 0;
		$imageTxt = "";
		// BG Images Directorty
		$bgDir = $this->root . '/Video/images/bg/';
		// Array of bg images
		$bgArray = array();
		if ($dh = opendir($bgDir)) {
			while (false !== ($dat = readdir($dh))) {
				if ($dat != "." && $dat != ".." && $dat != ".svn") {
					$bgArray[] = $bgDir . '/' . $dat;
				}
			}
			closedir($dh);
		}
		// Get random BG image
		$index = rand(0, count($bgArray) - 1);
		$bgImage = $bgArray[$index];
		// Break the Description into several lines
		$maxLines = 10;
		list($width, $height, $type, $attr) = getimagesize($bgImage);
		$this -> sw = $width;
		$this -> sh = $height;
		$maxTxtWidth = $width * .60;
		//$font = getenv ( "GDFONTPATH" ) . '/arial.ttf';
		$font = $this -> font;
		$textFontSize = 18;
		$descriptionArray = array();
		$txt = '';
		foreach (explode ( " ", $description ) as $word) {
			$txtBbox = imagettfbbox($textFontSize, 0, $font, $txt . $word);
			$txtWidth = abs($txtBbox[0] - $txtBbox[2]);
			if ($txtWidth < $maxTxtWidth) {
				$txt .= $word . " ";
				if (stripos($word, "/.") || stripos($word, "?") || stripos($word, "!")) {
					$txt = trim($txt);
					$descriptionArray[] = $txt;
					//echo ($txt . "<br>");
					$txt = '';
				}
			} else {
				$txt = trim($txt);
				$descriptionArray[] = $txt;
				//echo ($txt . "<br>");
				$txt = $word . " ";
			}
		}
		if (strlen($txt) > 0) {
			$descriptionArray[] = $txt;
			//echo ($txt . "<br>");
		}
		while (count($descriptionArray) > 0) {
			// Create Slide
			$slide = $this -> createSlide($bgImage, $link);
			// Add Title to slide
			$this -> addTitleToSlide($slide, $title);
			$white = imagecolorallocate($slide, 255, 255, 255);
			// Take up to max lines and add to slide
			$textLineCount = (count($descriptionArray) > $maxLines) ? $maxLines : count($descriptionArray);
			$txtArray = array();
			for ($i = 0; $i < $textLineCount; $i++) {
				$txtArray[] = array_shift($descriptionArray);
			}
			$this -> addCenteredText($slide, $txtArray, $font, $textFontSize, $white);
			// Save image to outputPath
			imagejpeg($slide, $outputPath . "image" . $imagecount . ".jpg", 100);
			// Store image path to txt file
			$nextImage = $outputPath . "image" . $imagecount . ".jpg";
			$imageTxt .= $nextImage . "\r\n";
			$imagecount++;
			// Free from memory
			imagedestroy($slide);
		}
		$outputImagesTxtFile = $outputPath . "images.txt";
		$this -> writeToFile($outputImagesTxtFile, $imageTxt);
		$this -> imageCount = $imagecount;
		//Return file path to text file with all created images
		return $outputImagesTxtFile;
	}

	function addCenteredText($slide, $txt, $font, $fontSize, $color, $yStart = null) {
		if (is_array($txt)) {
			$txtArray = $txt;
		} else {
			$txtArray = explode("/n", $txt);
		}
		$totalLineCount = count($txtArray);
		$lineCount = 0;
		$width = imagesx($slide);
		$height = imagesy($slide);
		$lineBbox = imagettfbbox($fontSize, 0, $font, "Test");
		$lineWidth = abs($lineBbox[0] - $lineBbox[2]);
		$lineHeight = abs($lineBbox[1] - $lineBbox[7]);
		$textHeight = $totalLineCount * $lineHeight * (3 / 2);
		if (isset($yStart)) {
			$lineYStart = $yStart - ($textHeight / 2) + $lineHeight;
		} else {
			$lineYStart = $this -> titleEnd + (($height - $this -> titleEnd - $textHeight) / 2);
		}
		foreach ($txtArray as $line) {
			$lineBbox = imagettfbbox($fontSize, 0, $font, $line);
			$lineWidth = abs($lineBbox[0] - $lineBbox[2]);
			$lineX = ($width - $lineWidth) / 2;
			imagettftext($slide, $fontSize, 0, $lineX, $lineYStart + ($lineCount * $lineHeight * (3 / 2)), $color, $font, $line);
			$lineCount++;
		}
	}

	function getRandomAudio() {
		// If no mp3 get a random audio to use
		// Audio Directorty
		$audioDir = $this->root . '/Video/audio/';
		// Array of audio
		$audioArray = array();
		if ($dh = opendir($audioDir)) {
			while (false !== ($dat = readdir($dh))) {
				if ($dat != "." && $dat != ".." && $dat != ".svn") {
					$audioArray[] = $audioDir . '/' . $dat;
				}
			}
			closedir($dh);
		}
		// Get random audio file
		$index = rand(0, count($audioArray) - 1);
		$mp3 = $audioArray[$index];
		return $mp3;
	}

	function createAudio($pid, $mp3, $imageLengthArray) {
		$audioTxt = "";
		$outputPath = $this->root . "/" . LocalServerVideoLocation . $pid . "/audio/";
		if (!file_exists($outputPath)) {
			mkdir($outputPath, 0777, true);
		}
		//Save audio locally
		$localfile = $outputPath . "audio1.mp3";
		$data = file_get_contents($mp3);
		file_put_contents($localfile, $data);
		$au = new AudioInfo();
		$info = $au -> Info($localfile);
		if (isset($info['error'])) {
			echo("Error reading audio info: " . print_r($info['error']) . "\r\n");
		}
		$this -> audioLength = $info["playing_time"];
		//echo ("<br>Audio Length: " . $this->audioLength . "<br>");
		//Splice audio into lengths determined by $imageLengthArray
		// Save audio to tmp file
		// Return path to text file contianing audio paths
		$audioTxt .= $localfile . "\r\n";
		$outputAudioTxtFile = $outputPath . "audio.txt";
		$this -> writeToFile($outputAudioTxtFile, $audioTxt);
		//Return file path to text file with all created images
		return $outputAudioTxtFile;
	}

	function createDirOnRemoteServer($dir) {
		$ip = 'ftp.' . RemoteHost;
		$ip = substr($ip, 0, strlen($ip) - 1);
		$ftpconn_id = ftp_connect($ip);
		$start = 6;
		$end = stripos(RemoteServer, ":", $start);
		$username = substr(RemoteServer, $start, $end - $start);
		$start = $end + 1;
		$end2 = stripos(RemoteServer, "@") + 1;
		$password = substr(RemoteServer, $start, $end2 - $start - 1);
		//echo ("U: $username P: $password<br>");
		if (ftp_login($ftpconn_id, $username, $password)) {
			ftp_pasv($ftpconn_id, 1);
			ftp_chdir($ftpconn_id, '/');
			$this -> ftp_mkdir_recursive($ftpconn_id, 0777, $dir);
		}
		ftp_close($ftpconn_id);
	}

	function ftp_mkdir_recursive($ftpconn_id, $mode, $path) {
		$dir = split("/", $path);
		$path = "";
		$ret = true;
		for ($i = 0; $i < count($dir); $i++) {
			$path .= "/" . $dir[$i];
			if (!@ftp_chdir($ftpconn_id, $path)) {
				@ftp_chdir($ftpconn_id, "/");
				if (!@ftp_mkdir($ftpconn_id, $path)) {
					$ret = false;
					break;
				} else {
					@ftp_chmod($ftpconn_id, $mode, $path);
				}
			}
		}
		return $ret;
	}

	function getDirFileCount($directory) {
		if (glob("$directory*.*") != false) {
			$filecount = count(glob("$directory*.*"));
		} else {
			$filecount = 0;
		}
		return $filecount;
	}

	function rrmdir($path) {
		return is_file($path) ? @unlink($path) : array_map(array($this, 'rrmdir'), glob($path . '/*')) == @rmdir($path);
	}

	function getProcessCount($processName = "TVCC.exe") {
		//$psCountFile = realpath(dirname(__FILE__) . '/CBUtils/Includes/pscount.exe');
		//$processCount = exec('call "' . $psCountFile . '" "'.$processName.'"');

		// create our system command
		$cmd = 'wmic process get description,creationdate | find "' . $processName . '"';

		// run the system command and assign output to a variable ($output)
		exec($cmd, $output, $result);

		$filteredOutput = array();
		foreach ($output as $o) {
			$foundProcessArray = explode(" ", $o);
			$foundProcess = $foundProcessArray[2];
			if (strcasecmp($foundProcess, $processName) === 0) {
				$filteredOutput[] = $o;
			}
		}

		$processCount = count($filteredOutput);
		return $processCount;
	}

	function createVideo($pid, $title, $description, $link, $mp3) {
		$status = "Nothing was done.";
		$format = "mp4";
		$outputFolder = $this->root . "/" . LocalServerVideoLocation . "$pid/";

		//echo ("Output Folder: $outputFolder");
		if (!file_exists($outputFolder)) {
			mkdir($outputFolder, 0777, true);
		}

		// Create Images for Video
		$pictureTextFile = $this -> createVideoImages($pid, $title, $description, $link);
		//echo ("Picture: $pictureTextFile<br>");
		// Create Audio for Video
		$audioTextFile = $this -> createAudio($pid, $mp3, null);
		//echo ("Audio: $audioTextFile<br>");
		$this -> spt = $this -> audioLength / $this -> imageCount;
		$tvccFile = 'TVCC.exe';
		//echo ("Output Folder: $outputFolder<br>");
		//Youtube  Format
		$this -> sw = 640;
		$this -> sh = 480;
		$outputPath = $outputFolder . "video/";
		if (!file_exists($outputPath)) {
			mkdir($outputPath, 0777, true);
		}
		$outputFile = $outputPath . $pid . "." . $format;
		if (!file_exists($outputFile)) {
			$tmpFile = $outputFolder . "tmp-" . $pid . "-$format.txt";
			$cmdstring = '" -fsv "' . $pictureTextFile . '" -fsa "' .$audioTextFile . '" -o "' . $outputFile . '" -vs ' . $this -> sw . 'x' . $this -> sh . ' -sw ' . $this -> sw . ' -sh ' . $this -> sh . ' -spt ' . $this -> spt . ' -smode';
			$descriptorspec = array(0 => array("pipe", "r"), // stdin is a pipe that the child will read from
			1 => array("pipe", "w"), // stdout is a pipe that the child will write to
			2 => array("file", $tmpFile, "a"));
			// stderr is a file to write to
			//echo ($tvccFile . ' ' . $cmdstring . "<br>");
			//echo  ('start "CBTE_Video_Creation_Process" /B /LOW /SEPARATE call "' . $tvccFile . " " . $cmdstring . "<br>");
			$pipes = "";

			$sleepTime = 60;
			while (($pCount = $this -> getProcessCount()) >= 1) {
				echo("Waiting $sleepTime seconds for TVCC process to use. $pCount Already Running<br>");
				sleep($sleepTime);
				if ($sleepTime > 10) {
					$sleepTime = ($sleepTime / 2) + rand(0, 3);
				}
			}

			$process = proc_open('call "' . $tvccFile . " " . $cmdstring, $descriptorspec, $pipes);

			//$process = proc_open ( 'start "CBTE_Video_Creation_Process" /B /NORMAL /SEPARATE call "' . $tvccFile . " " . $cmdstring, $descriptorspec, $pipes );
			//echo ('<td>');
			if (is_resource($process)) {
				while (!feof($pipes[1])) {
					$InputLine = fgets($pipes[1], 1024);
					// Wait utill input is complete
					if (strlen($InputLine) == 0)
						break;
				}
				fclose($pipes[0]);
				fclose($pipes[1]);
				$return_value = proc_close($process);
				//die("Video Finished!!!");
			} else {
				$status = "Video for $pid already existed in $outputPath";
				$return_value = 0;
			}
		}

		if ($return_value == 0) {
			$status = uploadFile($outputFile, $pid);
			if (strcasecmp($status, "success") == 0) {
				$status = "Video $pid Upload was successful";
				$transfered[] = $outputFolder;
			} else {
				$status = "Error uploading $outputFile: " . $status;
				$transfered[] = $outputFolder;
			}
		} else {
			$status .= "\n\rSomething went wrong while creating video for $pid";
			$transfered[] = $outputFolder;
			// Something went wrong. Add as transfered so that output folder is deleted
		}

		// Remove tmp Files after making video for all formats and was able to transfer them all to remote server
		foreach ($transfered as $t) {
			chown($t, 666);
			$this -> rrmdir($t);
		}
		return $status;
	}

}

$CBVC = new WPTVideoCreator();
?>