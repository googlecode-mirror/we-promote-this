<?php
/******************************************************************
Projectname:   Automatic Keyword Generator
Version:       0.2
Author:        Ver Pangonilo <smp_AT_itsp.info>
Last modified: 21 July 2006
Copyright (C): 2006 Ver Pangonilo, All Rights Reserved

 * GNU General Public License (Version 2, June 1991)
 *
 * This program is free software; you can redistribute
 * it and/or modify it under the terms of the GNU
 * General Public License as published by the Free
 * Software Foundation; either version 2 of the License,
 * or (at your option) any later version.
 *
 * This program is distributed in the hope that it will
 * be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.

Description:
This class can generates automatically META Keywords for your
web pages based on the contents of your articles. This will
eliminate the tedious process of thinking what will be the best
keywords that suits your article. The basis of the keyword
generation is the number of iterations any word or phrase
occured within an article.

This automatic keyword generator will create single words,
two word phrase and three word phrases. Single words will be
filtered from a common words list.

Change Log:
===========
0.2 Ver Pangonilo - 22 July 2005
================================
Added user configurable parameters and commented codes
for easier end user understanding.
						
0.3 Vasilich  (vasilich_AT_grafin.kiev.ua) - 26 July 2006
=========================================================
Added encoding parameter to work with UTF texts, min number 
of the word/phrase occurrences, 

0.4 Peter Kahl, B.S.E.E. (www.dezzignz.com) - 24 May 2009 
=========================================================
To strip the punctuations CORRECTLY, moved the ';' to the
end.

Also added '&nbsp;', '&trade;', '&reg;'.
 ******************************************************************/
class autokeyword {
	//declare variables
	//the site contents
	public $contents;
	public $encoding;
	//the generated keywords
	public $keywords;
	//minimum word length for inclusion into the single word
	//metakeys
	public $wordLengthMin;
	public $wordOccuredMin;
	//minimum word length for inclusion into the 2 word
	//phrase metakeys
	public $word2WordPhraseLengthMin;
	public $phrase2WordLengthMinOccur;
	//minimum word length for inclusion into the 3 word
	//phrase metakeys
	public $word3WordPhraseLengthMin;
	//minimum phrase length for inclusion into the 2 word
	//phrase metakeys
	public $phrase2WordLengthMin;
	public $phrase3WordLengthMinOccur;
	//minimum phrase length for inclusion into the 3 word
	//phrase metakeys
	public $phrase3WordLengthMin;
	public $allStopWords;
	public $contentURL;
	function autokeyword($contentURL, $params) {
		$this->contentURL = $contentURL;
		$result = $this->get_web_page ( $contentURL );
		if ($result ['errno'] != 0) {
			echo (' ... Error:  bad URL, timeout, or redirect loop ...');
		}
		if ($result ['http_code'] != 200) {
			echo (' ... Error:  no server, no permissions, or no page ...');
		}
		$encodedText = $result ['content'];
		$contentType = $result ['content_type'];
		preg_match ( '@([\w/+]+)(;\s+charset=(\S+))?@i', $contentType, $matches );
		$charset = $matches [3];
		$this->encoding = "utf-8";
		$text = iconv ( $charset, $this->encoding, $encodedText );
		$this->contents = $this->replace_chars ( $text );
		// single word
		$this->wordLengthMin = $params ['min_word_length'];
		$this->wordOccuredMin = $params ['min_word_occur'];
		// 2 word phrase
		$this->word2WordPhraseLengthMin = $params ['min_2words_length'];
		$this->phrase2WordLengthMin = $params ['min_2words_phrase_length'];
		$this->phrase2WordLengthMinOccur = $params ['min_2words_phrase_occur'];
		// 3 word phrase
		$this->word3WordPhraseLengthMin = $params ['min_3words_length'];
		$this->phrase3WordLengthMin = $params ['min_3words_phrase_length'];
		$this->phrase3WordLengthMinOccur = $params ['min_3words_phrase_occur'];
		//parse single, two words and three words
	}
	function getMetaKeywords() {
		$tags = get_meta_tags ( $this->contentURL );
		if(isset($tags ['keywords'])){
		return trim($tags ['keywords']);
		}else{
		    return null;
		}
	}
	function get_Contents() {
		return $this->contents;
	}
	function get_keywords() {
		$keywords = $this->parse_words () . $this->parse_2words () . $this->parse_3words ();
		return substr ( $keywords, 0, - 2 );
	}
	//turn the site contents into an array
	//then replace common html tags.
	function replace_chars($content) {
		//convert all characters to lower case
		mb_regex_encoding ( $this->encoding );
		$content = mb_strtolower ( $content, $this->encoding );
		$content = $this->strip_html_tags ( $content );
		$content = html_entity_decode ( $content, ENT_QUOTES, $this->encoding );
		$content = $this->strip_punctuation ( $content );
		$content = $this->strip_symbols ( $content );
		// replace multiple gaps
		$content = preg_replace ( '/ {2,}/si', " ", $content );
		return $content;
	}
	/**
	 * Get a web file (HTML, XHTML, XML, image, etc.) from a URL.  Return an
	 * array containing the HTTP server response header fields and content.
	 */
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
	/**
	 * Remove HTML tags, including invisible text such as style and
	 * script code, and embedded objects.  Add line breaks around
	 * block-level tags to prevent word joining after tag removal.
	 */
	function strip_html_tags($text) {
		$text = preg_replace ( array (// Remove invisible content
'@<head[^>]*?>.*?</head>@siu', '@<style[^>]*?>.*?</style>@siu', '@<script[^>]*?.*?</script>@siu', '@<object[^>]*?.*?</object>@siu', '@<embed[^>]*?.*?</embed>@siu', '@<applet[^>]*?.*?</applet>@siu', '@<noframes[^>]*?.*?</noframes>@siu', '@<noscript[^>]*?.*?</noscript>@siu', '@<noembed[^>]*?.*?</noembed>@siu', // Add line breaks before and after blocks
'@</?((address)|(blockquote)|(center)|(del))@iu', '@</?((div)|(h[1-9])|(ins)|(isindex)|(p)|(pre))@iu', '@</?((dir)|(dl)|(dt)|(dd)|(li)|(menu)|(ol)|(ul))@iu', '@</?((table)|(th)|(td)|(caption))@iu', '@</?((form)|(button)|(fieldset)|(legend)|(input))@iu', '@</?((label)|(select)|(optgroup)|(option)|(textarea))@iu', '@</?((frameset)|(frame)|(iframe))@iu' ), array (' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0", "\n\$0" ), $text );
		return strip_tags ( $text );
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
	function updateStopWords() {
		$stopWordsFilename = dirname(__FILE__)."/stopwords.txt";
		//$stopWordsFilename = "stopwords.txt";
		$stopText = file_get_contents ( $stopWordsFilename );
		$stopWords = mb_split ( '[ \n]+', trim ( mb_strtolower ( $stopText, $this->encoding ) ) );
		foreach ( $stopWords as $key => $word ) {
			$stopWords [$key] = trim ( $word );
		}
		$unwantedWordsFilename = dirname(__FILE__)."/unwantedwords.txt";
		//$unwantedWordsFilename = "unwantedwords.txt";
		$unwantedText = file_get_contents ( $unwantedWordsFilename );
		$unwantedWords = mb_split ( '[ \n]+', trim ( mb_strtolower ( $unwantedText, $this->encoding ) ) );
		foreach ( $unwantedWords as $key => $word ) {
			$unwantedWords [$key] = trim ( $word );
		}
		//list of commonly used words
		// this can be edited to suit your needs
		$common = array ("able", "about", "above", "act", "add", "afraid", "after", "again", "against", "age", "ago", "agree", "all", "almost", "alone", "along", "already", "also", "although", "always", "am", "amount", "an", "and", "anger", "angry", "animal", "another", "answer", "any", "appear", "apple", "are", "arrive", "arm", "arms", "around", "arrive", "as", "ask", "at", "attempt", "aunt", "away", "back", "bad", "bag", "bay", "be", "became", "because", "become", "been", "before", "began", "begin", "behind", "being", "bell", "belong", "below", "beside", "best", "better", "between", "beyond", "big", "body", "bone", "born", "borrow", "both", "bottom", "box", "boy", "break", "bring", "brought", "bug", "built", "busy", "but", "buy", "by", "call", "came", "can", "cause", "choose", "close", "close", "consider", "come", "consider", "considerable", "contain", "continue", "could", "cry", "cut", "dare", "dark", "deal", "dear", "decide", "deep", "did", "die", "do", "does", "dog", "done", "doubt", "down", "during", "each", "ear", "early", "eat", "effort", "either", "else", "end", "enjoy", "enough", "enter", "even", "ever", "every", "except", "expect", "explain", "fail", "fall", "far", "fat", "favor", "fear", "feel", "feet", "fell", "felt", "few", "fill", "find", "fit", "fly", "follow", "for", "forever", "forget", "from", "front", "gave", "get", "gives", "goes", "gone", "good", "got", "gray", "great", "green", "grew", "grow", "guess", "had", "half", "hang", "happen", "has", "hat", "have", "he", "hear", "heard", "held", "hello", "help", "her", "here", "hers", "high", "hill", "him", "his", "hit", "hold", "hot", "how", "however", "I", "if", "ill", "in", "indeed", "instead", "into", "iron", "is", "it", "its", "just", "keep", "kept", "knew", "know", "known", "late", "least", "led", "left", "lend", "less", "let", "like", "likely", "likr", "lone", "long", "look", "lot", "make", "many", "may", "me", "mean", "met", "might", "mile", "mine", "moon", "more", "most", "move", "much", "must", "my", "near", "nearly", "necessary", "neither", "never", "next", "no", "none", "nor", "not", "note", "nothing", "now", "number", "of", "off", "often", "oh", "on", "once", "only", "or", "other", "ought", "our", "out", "please", "prepare", "probable", "pull", "pure", "push", "put", "raise", "ran", "rather", "reach", "realize", "reply", "require", "rest", "run", "said", "same", "sat", "saw", "say", "see", "seem", "seen", "self", "sell", "sent", "separate", "set", "shall", "she", "should", "side", "sign", "since", "so", "sold", "some", "soon", "sorry", "stay", "step", "stick", "still", "stood", "such", "sudden", "suppose", "take", "taken", "talk", "tall", "tell", "ten", "than", "thank", "that", "the", "their", "them", "then", "there", "therefore", "these", "they", "this", "those", "though", "through", "till", "to", "today", "told", "tomorrow", "too", "took", "tore", "tought", "toward", "tried", "tries", "trust", "try", "turn", "two", "under", "until", "up", "upon", "us", "use", "usual", "various", "verb", "very", "visit", "want", "was", "we", "well", "went", "were", "what", "when", "where", "whether", "which", "while", "white", "who", "whom", "whose", "why", "will", "with", "within", "without", "would", "yes", "yet", "you", "young", "your", "br", "img", "p", "lt", "gt", "quot", "copy" );
		$this->allStopWords = array_merge ( $stopWords, $unwantedWords, $common );
		$this->allStopWords = array_unique ( $this->allStopWords );
		sort ( $this->allStopWords );
		//				echo("All Stop Words: ".$this->lineBreak);
	//				print_r($allStopWords);
	//				echo("".$this->lineBreak);
	}
	//single words META KEYWORDS
	function parse_words() {
		//create an array out of the site contents
		$s = mb_split ( '[ \n]+', $this->contents );
		//$s = split ( " ", $this->contents );
		$this->updateStopWords ();
		//initialize array
		$k = array ();
		//iterate inside the array
		foreach ( $s as $key => $val ) {
			//delete single or two letter words and
			//Add it to the list if the word is not
			//contained in the common words list.
			if (mb_strlen ( trim ( $val ) ) >= $this->wordLengthMin && ! in_array ( trim ( $val ), $this->allStopWords ) && ! is_numeric ( trim ( $val ) )) {
				$k [] = trim ( $val );
			}
		}
		//count the words
		$k = array_count_values ( $k );
		//sort the words from
		//highest count to the
		//lowest.
		$occur_filtered = $this->occure_filter ( $k, $this->wordOccuredMin );
		arsort ( $occur_filtered );
		//$imploded = $this->implode ( ", ", $occur_filtered );
		//release unused variables
		unset ( $k );
		unset ( $s );
		//return $imploded;
		return $occur_filtered;
	}
	function parse_2words() {
		//create an array out of the site contents
		//$x = split ( " ", $this->contents );
		$x = mb_split ( '[ \n]+', $this->contents );
		//initilize array
		//$y = array();
		for($i = 0; $i < count ( $x ) - 1; $i ++) {
			//delete phrases lesser than 5 characters
			if ((mb_strlen ( trim ( $x [$i] ) ) >= $this->word2WordPhraseLengthMin) && (mb_strlen ( trim ( $x [$i + 1] ) ) >= $this->word2WordPhraseLengthMin)) {
				$y [] = trim ( $x [$i] ) . " " . trim ( $x [$i + 1] );
			}
		}
		//count the 2 word phrases
		$y = array_count_values ( $y );
		$occur_filtered = $this->occure_filter ( $y, $this->phrase2WordLengthMinOccur );
		//sort the words from highest count to the lowest.
		arsort ( $occur_filtered );
		//$imploded = $this->implode ( ", ", $occur_filtered );
		//release unused variables
		unset ( $y );
		unset ( $x );
		//return $imploded;
		return $occur_filtered;
	}
	function parse_3words() {
		//create an array out of the site contents
		$a = mb_split ( '[ \n]+', $this->contents );
		//$a = split ( " ", $this->contents );
		//initilize array
		$b = array ();
		for($i = 0; $i < count ( $a ) - 2; $i ++) {
			//delete phrases lesser than 5 characters
			if ((mb_strlen ( trim ( $a [$i] ) ) >= $this->word3WordPhraseLengthMin) && (mb_strlen ( trim ( $a [$i + 1] ) ) > $this->word3WordPhraseLengthMin) && (mb_strlen ( trim ( $a [$i + 2] ) ) > $this->word3WordPhraseLengthMin) && (mb_strlen ( trim ( $a [$i] ) . trim ( $a [$i + 1] ) . trim ( $a [$i + 2] ) ) > $this->phrase3WordLengthMin)) {
				$b [] = trim ( $a [$i] ) . " " . trim ( $a [$i + 1] ) . " " . trim ( $a [$i + 2] );
			}
		}
		//count the 3 word phrases
		$b = array_count_values ( $b );
		//sort the words from
		//highest count to the
		//lowest.
		$occur_filtered = $this->occure_filter ( $b, $this->phrase3WordLengthMinOccur );
		arsort ( $occur_filtered );
		//$imploded = $this->implode ( ", ", $occur_filtered );
		//release unused variables
		unset ( $a );
		unset ( $b );
		//return $imploded;
		return $occur_filtered;
	}
	function occure_filter($array_count_values, $min_occur) {
		$occur_filtered = array ();
		foreach ( $array_count_values as $word => $occured ) {
			if ($occured >= $min_occur) {
				$occur_filtered [$word] = $occured;
			}
		}
		return $occur_filtered;
	}
	function implode($gule, $array) {
		$c = "";
		foreach ( $array as $key => $val ) {
			@$c .= $key . $gule;
		}
		return $c;
	}
}
?>
