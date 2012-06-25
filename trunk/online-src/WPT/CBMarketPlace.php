<?php
require_once 'CBUtils/CBAbstract.php';
require_once ('CBUtils/Includes/pclzip-2-8-2/pclzip.lib.php');
// Grabs the most recent xml file of the Click Bank Products and updates the Databse with the info
class CBMarketPlace extends CBAbstract {
	public $DBPoductFields;
	private $outputPath;
	private $dataFile;
	private $dbValues;
	private $csvSep;
	private $csvPrefix;
	private $lineTerminator;
	
	function constructClass() {
		$this->outputPath = "CBFeeds/";
		$this->dataFile = "dbFile.txt";
		$this->csvSep = "\t";
		$this->csvPrefix = 'CBMarketPlace-';
		$this->lineTerminator = "\n";
		echo ("<hr>Starting CB MarketPlace Search. Time: " . date ( "m-d-y h:i:s A" ) . "<br>");
		if ($this->areNewClickBankMarketPlaceFiles ()) {
		//if (true) {
			echo ("Updating DB. Time: " . date ( "m-d-y h:i:s A" ) . "<br>");
			$this->updateDB ();
			echo ("Finished Updating DB With New Products. Time: " . date ( "m-d-y h:i:s A" ) . "<br>");
		}
		echo ("Finished CB Market Place Search. Time: " . date ( "m-d-y h:i:s A" ) . "<br>");
	}
	function __destruct() {
		parent::__destruct ();
	}
	function areNewClickBankMarketPlaceFiles() {
		$success = false;
		$downloadTimeQery = "SELECT * FROM feeddownload order by id DESC limit 1";
		$results = mysql_query ( $downloadTimeQery );
		$row = mysql_fetch_array ( $results );
		$downloadTime = $row ["lastdownloadtime"];
		if (isset ( $downloadTime )) {
			echo ("Last Download Time: $downloadTime<br>");
			$today = date ( "Y-m-d h:i:s A" );
			$today = strtotime ( $today );
			$lastDownloadTime = strtotime ( $downloadTime );
			$daysDiff = floor ( abs ( $today - $lastDownloadTime ) / 60 / 60 / 24 );
		} else {
			$daysDiff = 1;
		}
		printf ( "There has been %d day(s) since the last downloadtime.<br>", $daysDiff );
		if ($daysDiff >= 1) {
			$success = $this->copyFile ( "http://www.clickbank.com/feeds/marketplace_feed_v2.xml.zip", $this->outputPath );
			//$success = true;
			if ($success) {
				// update new donwload date in database
				mysql_query ( "INSERT INTO feeddownload (lastdownloadtime) VALUE (NOW())" );
				$archive = new PclZip ( $this->outputPath . 'marketplace_feed_v2.xml.zip' );
				if ($archive->extract ( PCLZIP_OPT_PATH, $this->outputPath ) == 0) {
					echo ("Error : " . $archive->errorInfo ( true ));
					$success = false;
				} else {
					$success = true;
				}
			}
		}
		return $success;
	}
	function copyFile($url, $dirname) {
		@$file = fopen ( $url, "rb" );
		if (! $file) {
			echo "<font color=red>Failed to copy $url to $dirname !</font><br>";
			return false;
		} else {
			$filename = basename ( $url );
			$fc = fopen ( $dirname . "$filename", "wb" );
			while ( ! feof ( $file ) ) {
				$line = fgets ( $file );
				fwrite ( $fc, $line );
			}
			fclose ( $fc );
			echo "<font color=green>File <a href='$url'>$url</a> saved to $dirname !</font><br>";
			return true;
		}
	}
	
	function waitForLockAndWrite($file, $txt) {
		$fp = fopen ( $file, "a" );
		fwrite ( $fp, $txt . $this->lineTerminator );
		fclose ( $fp );
	}
	function clearFile($file) {
		if (file_exists ( $file )) {
			unlink ( $file );
		}
	}
	
	function loadDBFromFile($in_file, $in_dir = ".", $values = null, $tableName = "products", $ignoreLines = 0, $retry = 3) {
		chmod ( $in_dir, 0755 );
		$txtfile = $in_dir . $in_file;
		$loadsql = "LOAD DATA LOW_PRIORITY LOCAL INFILE '" . mysql_real_escape_string ( realpath ( $txtfile ) ) . "' REPLACE INTO TABLE " . $tableName . " FIELDS TERMINATED BY '" . $this->csvSep . "' LINES TERMINATED BY '" . $this->lineTerminator . "' STARTING BY '" . $this->csvPrefix . "'";
		if ($ignoreLines > 0) {
			$loadsql .= ' IGNORE ' . $ignoreLines . ' LINES';
		}
		if (isset ( $values ) && count ( $values ) > 0) {
			$loadsql .= " (" . implode ( ",", $values ) . ")";
		}
		$loadsql .= ";";
		
		mysql_query ( $loadsql );
		if (mysql_errno ()) {
			echo ("MySQL error " . mysql_errno () . ": " . mysql_error () . "\n<br>When executing:<br>\n$loadsql\n<br>");
			if (mysql_errno () == 2006 && $retry > 0) {
				echo ("Trying to reconnect and try again (" . (4 - $retry) . ")<br>");
				sleep ( 10 );
				$this->reconnectDB ();
				$this->loadDBFromFile ( $in_file, $in_dir, $values, $tableName, $ignoreLines, $retry - 1 );
			}
		} else {
			echo ("Data loaded into DB from file.<br>");
			$this->clearFile ( $txtfile );
			echo ($this->dataFile . " has been cleared.<Br>");
		}
	}
	
	function updateDataFile(array $values = null) {
		if (isset ( $values ) && is_array ( $values ) && count ( $values ) > 0) {
			ksort ( $values );
			$v = implode ( $this->csvSep, $values );
			$line = $this->csvPrefix . $v;
			if (! isset ( $this->dbValues )) {
				$this->dbValues = array_keys ( $values );
			}
			$txtfile = $this->outputPath . $this->dataFile;
			$this->waitForLockAndWrite ( $txtfile, $line );
		}
	}
	
	function updateDB() {
		$input = dirname ( __FILE__ ) . "/" . $this->outputPath . "marketplace_feed_v2.xml";
		//echo ("Input: " . $input . "<br>");
		$doc = new DOMDocument ( );
		$doc->preserveWhiteSpace = false;
		$doc->load ( $input );
		$xpath = new DOMXPath ( $doc );
		$categories = $doc->getElementsByTagName ( 'Category' );
		foreach ( $categories as $category ) {
			$categoryName = $xpath->query ( "Name", $category )->item ( 0 )->nodeValue;
			$sites = $xpath->query ( "Site", $category );
			foreach ( $sites as $site ) {
				$values = array ();
				$values ["category"] = mysql_real_escape_string ( $categoryName );
				$values ["id"] = mysql_real_escape_string ( $xpath->query ( "Id", $site )->item ( 0 )->nodeValue );
				$values ["popularityrank"] = $xpath->query ( "PopularityRank", $site )->item ( 0 )->nodeValue;
				$values ["title"] = mysql_real_escape_string ( $xpath->query ( "Title", $site )->item ( 0 )->nodeValue );
				$values ["description"] = mysql_real_escape_string ( $xpath->query ( "Description", $site )->item ( 0 )->nodeValue );
				$values ["hasrecurringproducts"] = $xpath->query ( "HasRecurringProducts", $site )->item ( 0 )->nodeValue;
				$values ["gravity"] = $xpath->query ( "Gravity", $site )->item ( 0 )->nodeValue;
				$values ["percentpersale"] = $xpath->query ( "PercentPerSale", $site )->item ( 0 )->nodeValue;
				$values ["percentperrebill"] = $xpath->query ( "PercentPerRebill", $site )->item ( 0 )->nodeValue;
				$values ["averageearningspersale"] = $xpath->query ( "AverageEarningsPerSale", $site )->item ( 0 )->nodeValue;
				$values ["initialearningspersale"] = $xpath->query ( "InitialEarningsPerSale", $site )->item ( 0 )->nodeValue;
				$values ["totalrebillamt"] = $xpath->query ( "TotalRebillAmt", $site )->item ( 0 )->nodeValue;
				$values ["referred"] = $xpath->query ( "Referred", $site )->item ( 0 )->nodeValue;
				$values ["commission"] = $xpath->query ( "Commission", $site )->item ( 0 )->nodeValue;
				$phpdate = $xpath->query ( "ActivateDate", $site )->item ( 0 )->nodeValue;
				$mysqldate = date ( 'Y-m-d', strtotime ( $phpdate ) );
				$values ["activatedate"] = $mysqldate;
				$this->updateDataFile ( $values );
				unset ( $values );
			}
		}
		$this->loadDBFromFile ( $this->dataFile, $this->outputPath, $this->dbValues );
	}
}
$CBMarketPlace = new CBMarketPlace ( );
?>





