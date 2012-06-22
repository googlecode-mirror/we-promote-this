<?php
require_once 'CBAbstract.php';
require_once 'Includes/magpierss-0.72/rss_fetch.inc';
class GrabScrapArticles extends CBAbstract {
	public $title;
	public $text;
	public $valid;
	function __construct() {
		parent::__construct ();
		$this->handleARGV (  );
	}
	function handleARGV() {
		global $argv;
		//echo ("Handle Argv Reached<br>");
		//echo ("Argv Count " . count ( $argv ) . "<br>");
		if (isset ( $argv ) && count ( $argv ) > 1) {
			$title = null;
			$keywords = array ();
			$dbTableName = null;
			$link = null;
			$pid = null;
			foreach ( $argv as $value ) {
				if (stripos ( $value, "=" ) !== false) {
					$keyArray = split ( "=", $value );
					$key = $keyArray [0];
					$keyValue = $keyArray [1];
					//echo("Key: $key - Value: $keyValue<br>");
					switch ($key) {
						case "dbTableName" :
							$dbTableName = $keyValue;
							break;
						case "pid" :
							$pid = $keyValue;
							break;
						case "link" :
							$link = $keyValue;
							break;
						case "title" :
							$title = $keyValue;
							break;
						case "keyword" :
							$keywords [] = $keyValue;
							//echo ("Keywords: $keywords<br>");
							break;
					}
				}
			}
			if (isset ( $title ) && count ( $keywords ) > 0) {
				//echo ("Building Article<br>");
				$this->buildArticle ( $title, $keywords, $pid );
			} else if (isset ( $dbTableName ) && isset ( $link )) {
				//echo ("Adding Tmp Article to DB<br>");
				$this->addToTmpArticleDB ( $dbTableName, $link );
			}
		}
	}
	function constructClass() {
		$this->valid = false;
	}
	function buildArticle($title, array $keywords, $pid = null) {
		$finalarticle = $this->getArticleFromFreeArticleDirectory ( $title, $keywords, $pid );
		if (isset ( $finalarticle ) && count ( $finalarticle ) == 2) {
			$this->text = $finalarticle ['text'];
			$this->title = $finalarticle ['title'];
			$this->valid = true;
		}
	}
	function isValid() {
		return $this->valid;
	}
	function getArticleLinksWithTitleKeyword($keyword, $limit = 100) {
		//echo ("Links Limit: $limit<br>");
		$links = array ();
		$feed = "http://www.ultimatearticledirectory.com/articlerss.php?type=4&q=" . urlencode ( $keyword );
		//echo("Searching <a href='".$feed."'>Feed</a><br>");
		$dom = new DOMDocument ( );
		$webPage = $this->get_web_page ( $feed );
		$xml = $webPage ['content'];
		//echo("Contents: $html<br><br>");
		@$dom->loadXML ( $xml );
		$xpath = new DOMXPath ( $dom );
		$span = $xpath->evaluate ( "//item/link/text()" );
		if ($span->length > 0) {
			foreach ( $span as $node ) {
				$links [] = $node->nodeValue;
				//echo ("Link Found: " . $node->nodeValue . "<br>");
				if (count ( $links ) >= $limit) {
					break;
				}
			}
		}
		return $links;
	}
	function getArticleFromFreeArticleDirectory($title, array $keywords, $pid = null) {
		// Search ultimate article directory using title
		$title = $this->strip_punctuation ( $this->strip_symbols ( $title ) );
		if (is_array ( $keywords )) {
			$keywordArray = $keywords;
		} else {
			$keywordArray = explode ( ",", $keywords );
		}
		$keywordArray [] = $title;
		$titlesPerKeyword = 5;
		//$titleSearchLimit = $titlesPerKeyword * count ( $keywordArray );
		$titleSearchLimit = 25;
		$links = array ();
		$keywords = implode ( " ", $keywordArray ); // keywords with spaces
		//echo ("Searching Directory for Title: $title<br>[ Keywords: $keywords]<br><br>");
		// If there are less articles found then the $titleSearchLimit use keywords to find more
		while ( count ( $links ) < $titleSearchLimit && count ( $keywordArray ) > 0 ) {
			$diff = $titleSearchLimit - count ( $links );
			$limit = ($diff < $titlesPerKeyword) ? $diff : $titlesPerKeyword;
			$keyword = array_shift ( $keywordArray );
			$newlinks = $this->getArticleLinksWithTitleKeyword ( $keyword, $limit );
			if (count ( $newlinks ) > 0) {
				array_splice ( $links, count ( $links ), 0, $newlinks );
			}
			$links = array_unique ( $links );
		}
		if (count ( $links ) == 0) {
			//echo ("No Articles Found<br>");
			return null; // No Articles Found
		} else {
			//echo (count ( $links ) . " Links found for pid: " . $pid);
			if (isset ( $pid )) {
				$suffix = $pid;
			} else {
				$suffix = date ( "m_d_y_His" );
			}
			$dbTableName = "scrap_articles_" . $suffix;
			$query = "DROP TABLE IF EXISTS $dbTableName;CREATE TEMPORARY TABLE $dbTableName ( id int( 11 ) NOT NULL auto_increment , text longtext NOT NULL , title text NOT NULL , PRIMARY KEY ( id ) , FULLTEXT KEY text ( title , text ), UNIQUE u (title(100), text(100)) );";
			//echo ("Running Query: $query<br><br>");
			$this->runBatchQuery ( $query );
			foreach ( $links as $link ) {
				$j = addslashes ( "dbTableName=$dbTableName link=$link" );
				$className = get_class ( $this );
				$cmd = $className . ".php $j";
				$this->getCommandLineHelper ()->startProcess ( $cmd, $className . ".txt" ); // threaded style (right away)
			//$this->getCommandLineHelper ()->run_in_background ( $cmd, $className . ".txt" ); // threaded style (waiting in task queue)
			//$this->addToTmpArticleDB ( $dbTableName, $link ); // Linear Style
			}
			$query = "SELECT id FROM $dbTableName";
			$maxWaitTime = 60;
			$time = 0;
			while ( mysql_num_rows ( mysql_query ( $query ) ) < count ( $links ) && $time < $maxWaitTime ) {
				sleep ( 5 ); // Wait for created table to fully populate
				$time += 5;
			}
			//Match Against original title
			mysql_query ( "OPTIMIZE TABLE $dbTableName" ); // Optimize The Table
			$query = "SELECT text, title, MATCH(title, text) AGAINST('$title' WITH QUERY EXPANSION) as score1, MATCH(title, text) AGAINST('$title' IN BOOLEAN MODE) as score2, MATCH(title, text) AGAINST('$keywords' WITH QUERY EXPANSION) as score3, MATCH(title, text) AGAINST('$keywords' IN BOOLEAN MODE) as score4 FROM $dbTableName order by ((score1/10)+score2+(score3/10)+score4) desc limit 1;";
			//echo ("Running Query: $query<br><br>");
			$finalArticle = $this->getFinalArticle ( $query );
			// Delete Created Table
			$query = "DROP TABLE IF EXISTS $dbTableName;";
			mysql_query ( $query );
			// Return final article selected text
			return $finalArticle;
		}
	}
	function getFinalArticle($query, $attempt = 0) {
		$finalArticle = array ();
		$results = mysql_query ( $query );
		if (mysql_errno () == 2006) {
			if ($attempt < 1) {
				// Try to reconnect
				$this->reconnectDB ();
				$finalArticle = $this->getFinalArticle ( $query, $attempt ++ );
			} else {
				$this->getLogger ()->logInfo ( 'Could not run query (' . $attempt . ' Attempts): ' . $query . '<br>Mysql Error (' . mysql_errno () . '): ' . mysql_error () );
			}
		} else if (mysql_errno ()) {
			$this->getLogger ()->logInfo ( 'Could not run query: ' . $query . '<br>Mysql Error (' . mysql_errno () . '): ' . mysql_error () );
		} else {
			$row = mysql_fetch_array ( $results );
			$finalArticle ['text'] = stripslashes ( $row ['text'] );
			$finalArticle ['title'] = stripslashes ( $row ['title'] );
		}
		
		return $finalArticle;
	}
	
	function addToTmpArticleDB($dbTableName, $link) {
		//Plug returned articles into tmp DB table with fulltext indexing using article titles only. include url for refferencing in next step
		$dom = new DOMDocument ( );
		$titles = array ();
		$webPage = $this->get_web_page ( $link );
		$html = $webPage ['content'];
		//echo("Contents: $html<br><br>");
		@$dom->loadHTML ( $html );
		$xpath = new DOMXPath ( $dom );
		$htmlTitle = $xpath->evaluate ( "//title/text()" );
		$tmpTitle = $htmlTitle->item ( 0 )->nodeValue;
		$tmpTitle = substr ( $tmpTitle, 0, strlen ( $tmpTitle ) - strlen ( "Ultimate Article Directory" ) ); // Remove 'Ultimate Article Directory' from end of title
		$titles [] = $tmpTitle;
		//echo ("Scrubbing article '$tmpTitle'' at: $link<br>");
		$span = $xpath->evaluate ( "//span[contains(@class,'articletext')]/text()" );
		// Grab body of article and store into Tmp DB Table
		$textLines = array ();
		if ($span->length > 0) {
			foreach ( $span as $node ) {
				//$out [] = mb_convert_encoding ( $node->nodeValue, $encodeType );
				$textLines [] = preg_replace ( '/[^\00-\255]+/u', '', $node->nodeValue );
				//echo ($node->nodeValue);
			}
		}
		$text = implode ( "\n\r", $textLines );
		// Limit Articles to  5000 words (spinnerchief imposed limit)
		$textArray = explode ( " ", $text );
		$textArray = array_slice ( $textArray, 0, (count ( $textArray ) < 5000) ? count ( $textArray ) : 5000 );
		$text = implode ( " ", $textArray );
		$tmpTitle = mysql_escape_string ( addslashes ( array_shift ( $titles ) ) );
		$tmpArticleText = mysql_escape_string ( addslashes ( $text ) );
		$articleInsertQuery .= "('" . $tmpArticleText . "','" . $tmpTitle . "'),";
		$articleInsertQuery = substr ( $articleInsertQuery, 0, strlen ( $articleInsertQuery ) - 1 );
		$query .= "INSERT IGNORE INTO $dbTableName (text,title) VALUES $articleInsertQuery";
		mysql_query ( $query );
	}
	function get_web_page($url) {
		$options = array (CURLOPT_RETURNTRANSFER => true, // return web page
CURLOPT_HEADER => false, // don't return headers
CURLOPT_FOLLOWLOCATION => true, // follow redirects
CURLOPT_ENCODING => "", // handle all encodings
CURLOPT_USERAGENT => "spider", // who am i
CURLOPT_AUTOREFERER => true, // set referer on redirect
CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
CURLOPT_TIMEOUT => 120, // timeout on response
CURLOPT_MAXREDIRS => 10 ); // stop after 10 redirects
		$ch = curl_init ( $url );
		curl_setopt_array ( $ch, $options );
		$content = curl_exec ( $ch );
		$err = curl_errno ( $ch );
		$errmsg = curl_error ( $ch );
		$header = curl_getinfo ( $ch );
		curl_close ( $ch );
		$header ['errno'] = $err;
		$header ['errmsg'] = $errmsg;
		$header ['content'] = $content;
		return $header;
	}
	function runBatchQuery($batchQuery) {
		//echo ("<br>Running multi_query update on DB");
		$con = $this->getDBConnection ()->getMysqliDBConnection ();
		$con->multi_query ( $batchQuery );
		do {
			//$con->use_result ()->close ();
			/* store first result set */
			if (($result = mysqli_store_result ( $con ))) {
				//do nothing since there's nothing to handle
				mysqli_free_result ( $result );
			}
			//echo "Okay\n";
		} while ( $con->next_result () );
		//echo ("Product Update Query: " . $ProductUpdateDBQuery);
		if ($con->errno) {
			$this->getLogger ()->logInfo ( "Could not run batch query MySQL error  : " . $con->error );
			//echo ("<br><br><br>---BATCH---<br><br><br>$batchQuery<br><br><br>---END BATCH---<br><br><br>");
		}
	}
	/**
	 * Strip symbols from text.
	 */
	function strip_symbols($text) {
		$plus = '\+\x{FE62}\x{FF0B}\x{208A}\x{207A}';
		$minus = '\x{2012}\x{208B}\x{207B}';
		$units = '\\x{00B0}\x{2103}\x{2109}\\x{23CD}';
		$units .= '\\x{32CC}-\\x{32CE}';
		$units .= '\\x{3300}-\\x{3357}';
		$units .= '\\x{3371}-\\x{33DF}';
		$units .= '\\x{33FF}';
		$ideo = '\\x{2E80}-\\x{2EF3}';
		$ideo .= '\\x{2F00}-\\x{2FD5}';
		$ideo .= '\\x{2FF0}-\\x{2FFB}';
		$ideo .= '\\x{3037}-\\x{303F}';
		$ideo .= '\\x{3190}-\\x{319F}';
		$ideo .= '\\x{31C0}-\\x{31CF}';
		$ideo .= '\\x{32C0}-\\x{32CB}';
		$ideo .= '\\x{3358}-\\x{3370}';
		$ideo .= '\\x{33E0}-\\x{33FE}';
		$ideo .= '\\x{A490}-\\x{A4C6}';
		return preg_replace ( array (// Remove modifier and private use symbols.
'/[\p{Sk}\p{Co}]/u', // Remove mathematics symbols except + - = ~ and fraction slash
'/\p{Sm}(?<![' . $plus . $minus . '=~\x{2044}])/u', // Remove + - if space before, no number or currency after
'/((?<= )|^)[' . $plus . $minus . ']+((?![\p{N}\p{Sc}])|$)/u', // Remove = if space before
'/((?<= )|^)=+/u', // Remove + - = ~ if space after
'/[' . $plus . $minus . '=~]+((?= )|$)/u', // Remove other symbols except units and ideograph parts
'/\p{So}(?<![' . $units . $ideo . '])/u', // Remove consecutive white space
'/ +/' ), ' ', $text );
	}
	/**
	 * Strip punctuation from text.
	 */
	function strip_punctuation($text) {
		$urlbrackets = '\[\]\(\)';
		$urlspacebefore = ':;\'_\*%@&?!' . $urlbrackets;
		$urlspaceafter = '\.,:;\'\-_\*@&\/\\\\\?!#' . $urlbrackets;
		$urlall = '\.,:;\'\-_\*%@&\/\\\\\?!#' . $urlbrackets;
		$specialquotes = '\'"\*<>';
		$fullstop = '\x{002E}\x{FE52}\x{FF0E}';
		$comma = '\x{002C}\x{FE50}\x{FF0C}';
		$arabsep = '\x{066B}\x{066C}';
		$numseparators = $fullstop . $comma . $arabsep;
		$numbersign = '\x{0023}\x{FE5F}\x{FF03}';
		$percent = '\x{066A}\x{0025}\x{066A}\x{FE6A}\x{FF05}\x{2030}\x{2031}';
		$prime = '\x{2032}\x{2033}\x{2034}\x{2057}';
		$nummodifiers = $numbersign . $percent . $prime;
		//updated in v0.3, 24 May 2009
		$punctuations = array (',', ')', '(', '.', "'", '"', '<', '>', '!', '?', '/', '-', '_', '[', ']', ':', '+', '=', '#', '$', '&quot;', '&copy;', '&gt;', '&lt;', '&nbsp;', '&trade;', '&reg;', ';', chr ( 10 ), chr ( 13 ), chr ( 9 ) );
		$text = str_replace ( $punctuations, " ", $text );
		return preg_replace ( array (// Remove separator, control, formatting, surrogate,
// open/close quotes.
		'/[\p{Z}\p{Cc}\p{Cf}\p{Cs}\p{Pi}\p{Pf}]/u', // Remove other punctuation except special cases
'/\p{Po}(?<![' . $specialquotes . $numseparators . $urlall . $nummodifiers . '])/u', // Remove non-URL open/close brackets, except URL brackets.
'/[\p{Ps}\p{Pe}](?<![' . $urlbrackets . '])/u', // Remove special quotes, dashes, connectors, number
// separators, and URL characters followed by a space
		'/[' . $specialquotes . $numseparators . $urlspaceafter . '\p{Pd}\p{Pc}]+((?= )|$)/u', // Remove special quotes, connectors, and URL characters
// preceded by a space
		'/((?<= )|^)[' . $specialquotes . $urlspacebefore . '\p{Pc}]+/u', // Remove dashes preceded by a space, but not followed by a number
'/((?<= )|^)\p{Pd}+(?![\p{N}\p{Sc}])/u', // Remove consecutive spaces
'/ +/' ), ' ', $text );
	}
}

$grabScrapArticles = new GrabScrapArticles ();
?>