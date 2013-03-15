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
        $this -> outputPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "CBUtils/CBFeeds/";
        if (!file_exists($this -> outputPath)) {
            mkdir($this -> outputPath, 0777, true);
        }
        $this -> dataFile = "dbFile.txt";
        $this -> csvSep = "\t";
        $this -> csvPrefix = 'CBMarketPlace-';
        $this -> lineTerminator = "\n";
        echo("<hr>Starting CB MarketPlace Search. Time: " . date("m-d-y h:i:s A") . "<br>");
        if ($this -> areNewClickBankMarketPlaceFiles()) {
            //if (true) {
            echo("Updating DB. Time: " . date("m-d-y h:i:s A") . "<br>");
            if ($this -> updateDB()) {
                // update new donwload date in database
                $insertQuery = "INSERT INTO feeddownload (lastdownloadtime) VALUE (NOW())";
                $this -> runQuery($insertQuery, $this -> getDBConnection() -> getDBConnection());
                echo("Finished Updating DB With New Products. Time: " . date("m-d-y h:i:s A") . "<br>");
            }
        }
        echo("Finished CB Market Place Search. Time: " . date("m-d-y h:i:s A") . "<br>");
    }

    function __destruct() {
        parent::__destruct();
    }

    function runQuery($query, $con, $returnAffectedRows = false, $retry = 3) {
        $affectedRowCount = 0;
        $results = $this -> getDBConnection() -> queryCon($query, $con);
        $affectedRowCount = $con -> affected_rows;
        if ($con -> errno) {
            if ($retry > 0 && $con -> errno == 2006) {
                //sleep(10 + rand(0, 15));
                if (!$con -> ping()) {
                    $this -> reconnectDB();
                    $con = $this -> getDBConnection() -> getMatchingCon($con);
                }
                return $this -> runQuery($query, $con, $returnAffectedRows, --$retry);

            } else {
                $this -> getLogger() -> log('Couldnt execute query: ' . $query . '<br>Mysql Error (' . $con -> errno . '): ' . $con -> error . " | Retries: " . (3 - $retry), PEAR_LOG_ERR);
            }
        }
        if ($returnAffectedRows) {
            return $affectedRowCount;
        } else {
            return $results;
        }
    }

    function areNewClickBankMarketPlaceFiles() {
        $success = false;
        $downloadTimeQery = "SELECT * FROM feeddownload order by id DESC limit 1";
        $results = $this -> runQuery($downloadTimeQery, $this -> getDBConnection() -> getDBConnection());
        $row = $results -> fetch_assoc();
        $downloadTime = $row["lastdownloadtime"];
        if (isset($downloadTime)) {
            echo("Last Download Time: $downloadTime<br>");
            $today = date("Y-m-d h:i:s A");
            $today = strtotime($today);
            $lastDownloadTime = strtotime($downloadTime);
            $daysDiff = floor(abs($today - $lastDownloadTime) / 60 / 60 / 24);
        } else {
            $daysDiff = 1;
        }
        printf("There has been %d day(s) since the last downloadtime.<br>", $daysDiff);
        if ($daysDiff >= 10) {
            // Log error
            $this -> getLogger() -> log("Error : There has been ".$daysDiff." day(s) since the last downloadtime", PEAR_LOG_ERR);
        }
        if ($daysDiff >= 7) {
            $success = $this -> copyFile(cbmarketplacefeed, $this -> outputPath);
            //$success = true;
            if ($success) {
                $archive = new PclZip($this -> outputPath . 'marketplace_feed_v2.xml.zip');
                if ($archive -> extract(PCLZIP_OPT_PATH, $this -> outputPath) == 0) {
                    $this -> getLogger() -> log("Error : " . $archive -> errorInfo(true), PEAR_LOG_ERR);
                    $success = false;
                } else {
                    echo("Marketplace zip file successfully unzipped.");
                    $success = true;
                }
            }
        }
        return $success;
    }

    function copyFile($url, $dirname) {
        @$file = fopen($url, "rb");
        if (!$file) {
            $this -> getLogger() -> log("<font color=red>Failed to copy $url to $dirname !</font><br>", PEAR_LOG_ERR);
            return false;
        } else {
            $filename = basename($url);
            $fc = fopen($dirname . "$filename", "wb");
            while (!feof($file)) {
                $line = fgets($file);
                fwrite($fc, $line);
            }
            fclose($fc);
            echo "<font color=green>File <a href='$url'>$url</a> saved to $dirname !</font><br>";
            return true;
        }
    }

    function waitForLockAndWrite($file, $txt) {
        $fp = fopen($file, "a");
        fwrite($fp, $txt . $this -> lineTerminator);
        fclose($fp);
    }

    function clearFile($file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }

    function loadDBFromFile($in_file, $in_dir = ".", $values = null, $tableName = "products", $ignoreLines = 0, $retry = 3) {
        $dataloaded = false;
        chmod($in_dir, 0755);
        $txtfile = $in_dir . $in_file;
        $loadsql = "LOAD DATA LOW_PRIORITY LOCAL INFILE '" . $this -> getDBConnection() -> getDBConnection() -> real_escape_string(realpath($txtfile)) . "' REPLACE INTO TABLE " . $tableName . " FIELDS TERMINATED BY '" . $this -> csvSep . "' LINES TERMINATED BY '" . $this -> lineTerminator . "' STARTING BY '" . $this -> csvPrefix . "'";
        if ($ignoreLines > 0) {
            $loadsql .= ' IGNORE ' . $ignoreLines . ' LINES';
        }
        if (isset($values) && count($values) > 0) {
            $loadsql .= " (" . implode(",", $values) . ")";
        }
        $loadsql .= ";";

        $affected = $this -> runQuery($loadsql, $this -> getDBConnection() -> getDBConnection(), true);
        if ($affected > 0) {
            echo("Data loaded (" . ($affected + 0) . " rows) into DB from file.<br>");
            $dataloaded = true;
            $this -> clearFile($txtfile);
            echo($this -> dataFile . " has been cleared.<Br>");

        } else {
            $this -> getLogger() -> log("<font color='red'>No data was loaded into the DB from ClickBank Market Place Feed</font><br>", PEAR_LOG_ERR);
        }
        return $dataloaded;
    }

    function updateDataFile(array $values = null) {
        if (isset($values) && is_array($values) && count($values) > 0) {
            ksort($values);
            $v = implode($this -> csvSep, $values);
            $line = $this -> csvPrefix . $v;
            if (!isset($this -> dbValues)) {
                $this -> dbValues = array_keys($values);
            }
            $txtfile = $this -> outputPath . $this -> dataFile;
            $this -> waitForLockAndWrite($txtfile, $line);
        }
    }

    function updateDB() {
        $input = $this -> outputPath . "marketplace_feed_v2.xml";
        //echo ("Input: " . $input . "<br>");
        $doc = new DOMDocument();
        $doc -> preserveWhiteSpace = false;
        $doc -> load($input);
        $xpath = new DOMXPath($doc);
        $categories = $doc -> getElementsByTagName('Category');
        foreach ($categories as $category) {
            $categoryName = $xpath -> query("Name", $category) -> item(0) -> nodeValue;
            $sites = $xpath -> query("Site", $category);
            foreach ($sites as $site) {
                $values = array();
                $values["category"] = $this -> getDBConnection() -> getDBConnection() -> real_escape_string($categoryName);
                $values["id"] = $this -> getDBConnection() -> getDBConnection() -> real_escape_string($xpath -> query("Id", $site) -> item(0) -> nodeValue);
                $values["popularityrank"] = $xpath -> query("PopularityRank", $site) -> item(0) -> nodeValue;
                $values["title"] = $this -> getDBConnection() -> getDBConnection() -> real_escape_string($xpath -> query("Title", $site) -> item(0) -> nodeValue);
                $values["description"] = $this -> getDBConnection() -> getDBConnection() -> real_escape_string($xpath -> query("Description", $site) -> item(0) -> nodeValue);
                $values["hasrecurringproducts"] = $xpath -> query("HasRecurringProducts", $site) -> item(0) -> nodeValue;
                $values["gravity"] = $xpath -> query("Gravity", $site) -> item(0) -> nodeValue;
                $values["percentpersale"] = $xpath -> query("PercentPerSale", $site) -> item(0) -> nodeValue;
                $values["percentperrebill"] = $xpath -> query("PercentPerRebill", $site) -> item(0) -> nodeValue;
                $values["averageearningspersale"] = $xpath -> query("AverageEarningsPerSale", $site) -> item(0) -> nodeValue;
                $values["initialearningspersale"] = $xpath -> query("InitialEarningsPerSale", $site) -> item(0) -> nodeValue;
                $values["totalrebillamt"] = $xpath -> query("TotalRebillAmt", $site) -> item(0) -> nodeValue;
                $values["referred"] = $xpath -> query("Referred", $site) -> item(0) -> nodeValue;
                $values["commission"] = $xpath -> query("Commission", $site) -> item(0) -> nodeValue;
                $phpdate = $xpath -> query("ActivateDate", $site) -> item(0) -> nodeValue;
                $mysqldate = date('Y-m-d', strtotime($phpdate));
                $values["activatedate"] = $mysqldate;
                $this -> updateDataFile($values);
                unset($values);
            }
        }
        return $this -> loadDBFromFile($this -> dataFile, $this -> outputPath, $this -> dbValues);
    }

}

$CBMarketPlace = new CBMarketPlace();
?>

