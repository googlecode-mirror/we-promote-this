<?php
require_once 'CBAbstract.php';
require_once 'Zend/Loader.php'; // the Zend dir must be in your include_path
Zend_Loader::loadClass ( 'Zend_Http_Client' );
//require_once 'Http/Client.php';
class Categorizer extends CBAbstract {
	public $mainPID;
	public $keywords;
	public $baseUrl;
	public $catName;
	public $catID;
	public $subCatName;
	public $subCatID;
	
	public $possCatName;
	public $possCatID;
	public $possSubCatName;
	public $possSubCatID;
	
	function __construct($pid) {
		$this->mainPID = $pid;
		parent::__construct ();
	}
	
	function constructClass() {
		// Get Category and keywords from database
		$query = "SELECT category, words FROM products LEFT JOIN keywords USING(id) WHERE id='" . $this->mainPID . "'";
		$results = mysql_query ( $query );
		$row = mysql_fetch_array ( $results );
		$cat = htmlspecialchars_decode ( $row ['category'] );
		//$cat =  $row ['category'] ;
		//echo ("Cat for " . $this->mainPID . " in ClickBank is $cat<br>");
		$this->keywords = array ($cat );
		$dbKeywords = json_decode ( $row ['words'], true );
		array_splice ( $this->keywords, count ( $this->keywords ), 0, $dbKeywords );
		//echo ("Keywords: ");
		//print_r ( $this->keywords );
		//echo ("<br>");
		$q = urlencode ( implode ( ",", $this->keywords ) );
		$this->baseUrl = "http://www.google.com/insights/search/overviewReport?cmpt=q&content=1&q=$q";
		
	//echo ("URL: " . $this->baseUrl . "<br>");
	}
	
	function isValid() {
		return (count ( $this->keywords ) > 0);
	}
	
	function htmlkarakter($string) {
		$string = str_replace ( array ("&lt;", "&gt;", '&amp;', '&#039;', '&quot;', '&lt;', '&gt;' ), array ("<", ">", '&', '\'', '"', '<', '>' ), htmlspecialchars_decode ( $string ) );
		return $string;
	}
	
	function getCategoryName() {
		return $this->catName;
	}
	function getCategoryID() {
		return $this->catID;
	}
	
	function getSubCategoryName() {
		return $this->subCatName;
	}
	function getSubCategoryID() {
		return $this->subCatID;
	}
	
	function getPossCategoryName() {
		return $this->possCatName;
	}
	function getPossCategoryID() {
		return $this->possCatID;
	}
	
	function getPossSubCategoryName() {
		return $this->possSubCatName;
	}
	function getPossSubCategoryID() {
		return $this->possSubCatID;
	}
	
	function getGoogleCats($catfilter = null) {
		$categories = array ();
		$url = $this->baseUrl;
		if (isset ( $catfilter )) {
			$url .= "&cat=" . $catfilter;
		}
		$client = new Zend_Http_Client ( $url );
		$client->setHeaders ( array ('Accept-Encoding: gzip, deflate', 'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:2.0) Gecko/20100101 Firefox/4.0', 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8', 'Accept-Language: en-us,en;q=0.5', 'Accept-Charset: utf-8', 'Keep-Alive:	115', 'Connection: keep-alive', 'Referer: http://www.google.com/insights/search/', 'Host: www.google.com', 'Cookie: __utma=173272373.1982475044.1304967867.1304967867.1304967867.1; __utmb=173272373.13.10.1304967867; __utmc=173272373; __utmz=173272373.1304967867.1.1.utmcsr=(direct)|utmccn=(direct)|utmcmd=(none); __utmv=; I4SUserLocale=en_US; PREF=ID=c38915118fa8ca87:U=05f7918681e6068f:FF=0:TM=1304967819:LM=1304967820:S=PeALfwlEG4eYO0o2; NID=46=B1s2mQEDHslDDrqidjay_RR2lWRkHmxmWSEZDtwLdQ2U5Q9dgPGug24oBxi8N_CYx2556xwlFSPsZrduuhLKcWtkR4pjmchiu1jlUaCSfIPwjbeNcgly4CJJ4JZXfN49; S=izeitgeist-ad-metrics=WmGMSj79eTE' ) );
		try {
			$clientResponse = $client->request ( Zend_Http_Client::GET );
			$html = $clientResponse->getBody ();
			$html = $this->htmlkarakter ( $html );
			//echo ("HTML :<br>");
			//var_dump ( $html );
			//echo ("<br>");
			if (isset ( $catfilter )) {
				$search = '<span class="reportCategoryBreadcrumbs">Subcategories:&nbsp;';
			} else {
				$search = '<span class="reportCategoryBreadcrumbs">Categories:&nbsp;';
			}
			$start = strpos ( $html, $search ) + strlen ( $search );
			$end = strpos ( $html, "</span>", $start );
			$catText = substr ( $html, $start, $end - $start );
			//echo ("Cat Text: $catText<br>");
			

			$search = "&cat=";
			$regexp = "<a\s[^>]*href=(\"??)([^\" >]*?)\\1[^>]*>(.*)<\/a>";
			//foreach ( $googleCats as $cat ) {
			

			if (preg_match_all ( "/$regexp/siU", $catText, $matches, PREG_SET_ORDER )) {
				foreach ( $matches as $match ) {
					$link = $match [2]; // link address 
					$category = $match [3]; // link text
					$start = strpos ( $link, $search ) + strlen ( $search );
					$end = strpos ( $link, '&', $start );
					$categoryID = substr ( $link, $start, $end - $start );
					$categories [$categoryID] = $category;
					
				//echo ("Google Cat ID: " . $categoryID . " = $category<br>");
				

				}
			}
			
		//echo ("Categories :<br>");
		//print_r ( $categories );
		//echo ("<br>");
		} catch ( Zend_Exception $e ) {
			echo ("Error: Could not find any relavant categories for " . $this->mainPID . " using keywords:<br>");
			print_r ( $this->keywords );
			echo ("<br>" . $e->getMessage () . "<br>");
		} catch ( Exception $e ) {
			echo ("There was an exceptions " . $e->getMessage () . "<br>");
		}
		return $categories;
	
	}
	
	function insertCatIntoDBTable(array $categories, $dbTableName) {
		$catInsertQuery = "";
		foreach ( $categories as $catID => $category ) {
			// remove & from $category
			$category = str_replace ( "&", "", $category );
			$catInsertQuery .= "('" . $catID . "','" . $category . "'),";
		}
		$catInsertQuery = substr ( $catInsertQuery, 0, strlen ( $catInsertQuery ) - 1 );
		$query = "INSERT IGNORE INTO $dbTableName (id,cat) VALUES $catInsertQuery;";
		//echo ("Insert Cat Query: $query<br>");
		mysql_query ( $query );
		mysql_query ( "OPTIMIZE TABLE $dbTableName" ); // Optimize The Table
	}
	
	function getBestCat(array $categories, array $possibleCategories, $tableNameSuffix = null) {
		// Add Categories To Tmp table to do full text match
		$dbTableName = "tmp_" . $this->mainPID;
		if (isset ( $tableNameSuffix )) {
			$dbTableName .= "_" . $tableNameSuffix;
		}
		$query = "DROP TABLE IF EXISTS $dbTableName;";
		mysql_query ( $query );
		$query = "CREATE TEMPORARY TABLE $dbTableName ( id TEXT NOT NULL, cat TEXT NOT NULL , FULLTEXT KEY text ( cat ) );";
		mysql_query ( $query );
		//echo ("Create Table Query: $query<br>");
		$this->insertCatIntoDBTable ( $categories, $dbTableName );
		
		//Match Against each possible cat
		$catMap = array ();
		$possCatMap = array ();
		foreach ( $possibleCategories as $icat => $cat ) {
			$cat = urlencode ( $cat );
			$query = "SELECT id, cat, MATCH(cat) AGAINST('$cat') as score1, MATCH(cat) AGAINST('$cat' WITH QUERY EXPANSION) as score2, MATCH(cat) AGAINST('$cat' IN BOOLEAN MODE) as score3 FROM $dbTableName having ((score1/10)+(score2/10)+score3)>0 order by ((score1/10)+(score2/10)+score3) desc limit 1;";
			//echo ("Match Query: $query<br>");
			$results = mysql_query ( $query );
			$row = mysql_fetch_assoc ( $results );
			$id = $row ['id'];
			$qCat = urldecode ( $row ['cat'] );
			$index = "$id=$qCat";
			$possCatMap [$index] = "$icat=$cat";
			if (array_key_exists ( $index, $catMap )) {
				//echo ("Increasing Value of Cat: " . $qCat . " for key: " . $cat . "<br>");
				$catMap [$index] += 1;
			} else {
				$catMap [$index] = 1;
				
			//echo ("Setting Value of Cat: " . $qCat . " to 1 for key: " . $cat . "<br>");
			}
		}
		
		// Sort by highest frequency
		arsort ( $catMap );
		
		//$googleCatMap = $catMap;
		$googleCatMap = array_slice ( $catMap, 0, ceil ( count ( $catMap ) / 2 ) );
		
		//echo ("Google Cat Map:<br>");
		//var_dump ( $googleCatMap );
		//echo ("<br><br>");
		

		// Empty CatMap
		$catMap = array ();
		
		// Empty Table
		$query = "TRUNCATE TABLE $dbTableName";
		mysql_query ( $query );
		
		// Add Possible Cats to Database
		$this->insertCatIntoDBTable ( $possibleCategories, $dbTableName );
		
		//Match Against Keywords
		foreach ( $this->keywords as $ikeyword => $keyword ) {
			$query = "SELECT id, cat, MATCH(cat) AGAINST('$keyword') as score1, MATCH(cat) AGAINST('$keyword' WITH QUERY EXPANSION) as score2, MATCH(cat) AGAINST('$keyword' IN BOOLEAN MODE) as score3 FROM $dbTableName having ((score1/10)+(score2/10)+score3)>0 order by ((score1/10)+(score2/10)+score3) desc limit 3;";
			//die ( "Match Query: $query<br>" );
			$results = mysql_query ( $query );
			$dt = 3;
			while ( ($row = mysql_fetch_assoc ( $results )) ) {
				$id = $row ['id'];
				$qCat = urlencode ( $row ['cat'] );
				$index = "$ikeyword=$keyword";
				$possCatMap [$index] = "$id=$qCat";
				if (array_key_exists ( $index, $catMap )) {
					//echo ("Increasing Value of Cat: " . $qCat . " for key: " . $cat . "<br>");
					$catMap [$index] += $dt;
				} else {
					$catMap [$index] = $dt;
					
				//echo ("Setting Value of Cat: " . $qCat . " to 1 for key: " . $cat . "<br>");
				}
				$dt --;
			}
		
		}
		
		// Sort by highest frequency
		arsort ( $catMap );
		
		$keywordCatMap = array_slice ( $catMap, 0, ceil ( count ( $catMap ) / 2 ) );
		//$keywordCatMap = $catMap;
		

		//echo ("Keyword Cat Map:<br>");
		//var_dump ( $keywordCatMap );
		//echo ("<br><br>");
		

		// Empty CatMap
		$catMap = array ();
		
		// Empty Table
		$query = "TRUNCATE TABLE $dbTableName";
		mysql_query ( $query );
		
		//Combine keywordCatMap and GoogleCatMap
		$bestCatMap = array_merge ( $keywordCatMap, $googleCatMap );
		
		//echo ("Best Cats Map:<br>");
		//var_dump ( $bestCatMap );
		//echo ("<br><br>");
		

		$bestCatsArray = array ();
		$sortedKeys = array_keys ( $bestCatMap );
		foreach ( $sortedKeys as $key ) {
			$keyArray = explode ( "=", $key );
			$index = $keyArray [0];
			$bestCatsArray [$index] = $keyArray [1];
		}
		
		//echo ("Best Cats Array:<br>");
		//var_dump ( $bestCatsArray );
		//echo ("<br><br>");
		

		// Add Best Cats to Database
		$this->insertCatIntoDBTable ( $bestCatsArray, $dbTableName );
		
		//Match Against Keywords
		foreach ( $this->keywords as $keyword ) {
			$query = "SELECT id, cat, MATCH(cat) AGAINST('$keyword') as score1, MATCH(cat) AGAINST('$keyword' IN BOOLEAN MODE) as score2 FROM $dbTableName order by ((score1/10)+score2) desc limit 3;";
			//echo ("Match Query: $query<br>");
			$results = mysql_query ( $query );
			$dt = 3;
			while ( ($row = mysql_fetch_assoc ( $results )) ) {
				$id = $row ['id'];
				$qCat = $row ['cat'];
				$index = "$id=$qCat";
				if (array_key_exists ( $index, $catMap )) {
					//echo ("Increasing Value of Cat: " . $qCat . " for key: " . $cat . "<br>");
					$catMap [$index] += $dt;
				} else {
					$catMap [$index] = $dt;
					//echo ("Setting Value of Cat: " . $qCat . " to 1 for key: " . $cat . "<br>");
				}
				$dt --;
			}
		}
		
		// Sort by highest frequency
		arsort ( $catMap );
		
		//echo ("Cat Map:<br>");
		//var_dump ( $catMap );
		//echo ("<br><br>");
		

		//echo ("Poss Cat Map:<br>");
		//var_dump ( $possCatMap );
		//echo ("<br><br>");
		

		$sortedKeys = array_keys ( $catMap );
		$key = $sortedKeys [0];
		$bestCatArray = explode ( "=", $key );
		$bestPossCatArray = explode ( "=", $possCatMap [$key] );
		//$val = $catMap [$key];
		$bestCatID = $bestCatArray [0];
		$bestCat = $bestCatArray [1];
		$bestPossCatID = $bestPossCatArray [0];
		$bestPossCat = $bestPossCatArray [1];
		//echo ("Best Cat ID: $bestCatID<br>");
		//echo ("Best Cat: $bestCat<br>");
		//echo ("Best Cat Count: $val<br>");
		

		//Delete Created Table
		$query = "DROP TABLE IF EXISTS $dbTableName;";
		mysql_query ( $query );
		
		return array ("catname" => urldecode ( $bestCat ), "catid" => $bestCatID, 'posscatname' => urldecode ( $bestPossCat ), 'posscatid' => $bestPossCatID );
	}
	
	function chooseCategory(array $possibleCategories) {
		// Match the keywords against the possible categories and return most relevant category
		if ($this->isValid ()) {
			$categories = $this->getGoogleCats ();
			$results = $this->getBestCat ( $categories, $possibleCategories, "cat" );
			$this->catID = $results ['catid'];
			$this->catName = $results ['catname'];
			$this->possCatID = $results ['posscatid'];
			$this->possCatName = $results ['posscatname'];
		}
	}
	function chooseSubCategory(array $possibleSubCategories) {
		// Match the keywords agains the possible categories and return most relevant sub category under $category
		if ($this->isValid () && isset ( $this->catID )) {
			//$categories = $this->getGoogleCats ( $this->catID );
			$categories = $this->getGoogleCats ();
			$results = $this->getBestCat ( $categories, $possibleSubCategories, "subcat" );
			$this->subCatID = $results ['catid'];
			$this->subCatName = $results ['catname'];
			$this->possSubCatID = $results ['posscatid'];
			$this->possSubCatName = $results ['posscatname'];
		}
	}
}
//echo ("<pre>");


/*
$obj = new Categorizer ( 'HENRIQUE66' );
$possibleCats = array ();
$possibleSubCats = array ();
$dir = dirname ( __FILE__ ) . '/../Programs/';
echo ("My Path: " . realpath ( $dir . '/CategoryClasses.txt' ) . "<br>");
$possText = file ( realpath ( $dir . '/CategoryClasses.txt' ) );
$possibleCatsTemp = file ( realpath ( $dir . '/CategoryClasses.txt' ), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );
$possibleSubCatsTemp = file ( realpath ( $dir . '/PossibleCategories.txt' ), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES );

foreach ( $possibleCatsTemp as $index ) {
	$value = str_replace ( 'amp;', '', $index );
	$value = str_replace ( '&', '', $value );
	$value = str_replace ( '::', ' ', $value );
	$value = str_replace ( '/', ' ', $value );
	$value = str_replace ( '  ', ' ', $value );
	//$catSplit = split('::',$value);
	//$mainCat = trim($catSplit[0]);
	//$subCat = trim($catSplit[1]);
	//$subCatIndex = 0;
	//foreach(explode(" ", $subCat) as $subCatValue){
	//		$possibleCats [$index."::subindex_".$subCat] = $subCatValue
	

	//}
	//$value = str_replace ( ' ', ' OR ', $value );
	$possibleCats [$index] = $value;
}

echo ("Possible Cats:<br>");
print_r ( $possibleCats );
echo ("<br><br><br>");

$obj->chooseCategory ( $possibleCats );
echo ("Google Cat Name: " . $obj->getCategoryName () . "<br>");
echo ("Google Cat ID: " . $obj->getCategoryID () . "<br>");
echo ("Choosen Cat Name: " . $obj->getPossCategoryName () . "<br>");
echo ("Choosen Cat ID: " . $obj->getPossCategoryID () . "<br>");

foreach ( $possibleSubCatsTemp as $index ) {
	$value = str_replace ( 'amp;', '', $index );
	$value = str_replace ( '&', '', $value );
	$value = str_replace ( '::', '', $value );
	$value = str_replace ( '/', ' ', $value );
	$value = str_replace ( '  ', ' ', $value );
	$possibleSubCats [$index] = $value;
}

echo ("Possible Sub Cats:<br>");
print_r ( $possibleSubCats );
echo ("<br><br>");

$obj->chooseCategory ( $possibleCats );
$obj->chooseSubCategory ( $possibleSubCats );

echo ("Google subCat Name: " . $obj->getSubCategoryName () . "<br>");
echo ("Google subCat ID: " . $obj->getSubCategoryID () . "<br>");
echo ("Choosen subCat Name: " . $obj->getPossSubCategoryName () . "<br>");
echo ("Choosen subCat ID: " . $obj->getPossSubCategoryID () . "<br>");

*/

//echo ("</pre>");
?>