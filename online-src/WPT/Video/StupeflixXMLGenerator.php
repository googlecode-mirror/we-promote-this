<?php

class StupeflixXMLGenerator {
	
	public $doc;
	public $xPath;
	public $stackNode;
	public $pid;
	public $title;
	public $description;
	public $productImages;
	public $link;
	public $bgImage;
	public $bgAudio;
	public $endTimeSpace;
	public $startTimeSpace;
	public $audioVoice;
	
	function __construct($pid, $title, array $description, array $productImages, $bgImage, $bgAudio) {
		$template = file_get_contents ( dirname ( __FILE__ ) . "/Includes/movie-template.xml" );
		$this->doc = new DOMDocument ();
		$this->doc->loadXML ( $template );
		$this->xPath = new DOMXPath ( $this->doc );
		$xQuery = $this->xPath->query ( '//stack' );
		$this->stackNode = $xQuery->item ( 0 );
		// Limit title to a length of 30 chars
		if (strlen ( $title ) > 30) {
			$title = substr ( $title, 0, 30 );
			$titleArray = explode ( " ", $title );
			array_pop ( $titleArray );
			$title = implode ( " ", $titleArray ) . " ...";
		}
		$this->pid = $pid;
		$this->title = $title;
		$this->description = $description;
		$this->productImages = $productImages;
		$this->link = "www.ChrisQueen.com/CB/" . $pid;
		$this->bgImage = $bgImage;
		$this->bgAudio = $bgAudio;
		$this->endTimeSpace = 20;
		$this->startTimeSpace = 5;
		$voices = array ("julie", "paul" );
		$this->audioVoice = $voices [array_rand ( $voices )];
		$this->createXML ();
	
	}
	
	function saveXML($filename) {
		$this->doc->save ( $filename );
		$data = file_get_contents ( $filename );
		// Need to convert htmlspecialchars back to regular chars
		$data = htmlspecialchars_decode ( $data ); // Keep
		file_put_contents ( $filename, $data );
	}
	
	function createXML() {
		// Add bg Image
		$image = $this->createNode ( "image", array ("filename" => $this->bgImage ) );
		$effect = $this->createNode ( "effect", array ("type" => "none" ), array ($image ) );
		$this->appendChild ( $this->stackNode, $effect );
		
		// Add Title Text
		$animator = $this->createNode ( "animator", array ("type" => "slide-out", "direction" => "up", "margin-end" => $this->endTimeSpace, "duration" => "1.5" ) );
		$filter = $this->createNode ( "filter", array ("type" => "distancemap", "distanceWidth" => "40.0" ) );
		$filter2 = $this->createNode ( "filter", array ("type" => "distancecolor", "distanceWidth" => "40.0", "color" => "#ff0000", "strokeColor" => "#ffffff", "strokeOpacity" => "1.0", "strokeWidth" => "0.02", "innerShadowColor" => "#ff3333", "innerShadowOpacity" => "1.0", "innerShadowPosition" => "0.01,-0.01", "dropShadowColor" => "#000000", "dropShadowOpacity" => "1.0", "dropShadowBlurWidth" => "0.9", "dropShadowPosition" => "0.01,-0.01" ) );
		$key = $this->createNode ( "key", array ("time" => "0.0", "pos" => "0,0.85,0" ) );
		$animator2 = $this->createNode ( "animator", array ("type" => "custom" ), array ($key ) );
		$titleText = $this->createNode ( "text", array ("type" => "zone", "vector" => "true", "fontcolor" => "#ff0000", "fontsize" => "20", "weight" => "bold", "stretch" => "condensed", "face" => "times", "align" => "center,center" ), array ($animator, $filter, $filter2, $animator2 ), $this->title );
		$this->appendChild ( $this->stackNode, $titleText );
		
		// Add footer background
		$image = $this->createNode ( "image", array ("color" => "#ffffff" ) );
		$filter = $this->createNode ( "filter", array ("type" => "alpha", "margin-start" =>"0.0", "margin-end" => $this->endTimeSpace+1.5, "alphaStart" => "0.7", "alphaEnd" => "0.7" ) );
		$filter2 = $this->createNode ( "filter", array ("type" => "alpha", "margin-end" => $this->endTimeSpace, "alphaStart" => "0.7", "alphaEnd" => "0.0", "duration" => "1.5" ) );
		$effect = $this->createNode ( "effect", array ("type" => "none" ), array ($image, $filter,$filter2 ) );
		$overlay = $this->createNode ( "overlay", array ("width" => "1.0", "bottom" => "0.0", "height" => "0.20" ), array ($effect ) );
		$this->appendChild ( $this->stackNode, $overlay );
		
		// Add footer Text 1
		$filter = $this->createNode ( "filter", array ("type" => "alpha", "margin-end" => $this->endTimeSpace, "alphaStart" => "1.0", "alphaEnd" => "0.0", "duration" => "1.5" ) );
		$filter2 = $this->createNode ( "filter", array ("type" => "distancemap", "distanceWidth" => "40.0" ) );
		$filter3 = $this->createNode ( "filter", array ("type" => "distancecolor", "distanceWidth" => "40.0", "color" => "#000000", "strokeColor" => "#ffffff", "strokeOpacity" => "1.0", "strokeWidth" => "0.02", "dropShadowColor" => "#000000", "dropShadowOpacity" => "1.0", "dropShadowBlurWidth" => "0.9", "dropShadowPosition" => "0.01,-0.01" ) );
		$key = $this->createNode ( "key", array ("time" => "0.0", "pos" => "0,-0.70,0" ) );
		$animator2 = $this->createNode ( "animator", array ("type" => "custom" ), array ($key ) );
		$footerText1 = $this->createNode ( "text", array ("type" => "zone", "vector" => "true", "fontsize" => "16", "stretch" => "condensed", "face" => "times", "align" => "center,center" ), array ($filter, $filter2, $filter3, $animator2 ), "Find Out More Now:" );
		$this->appendChild ( $this->stackNode, $footerText1 );
		
		// Add footer Text 2
		$filter = $this->createNode ( "filter", array ("type" => "alpha", "margin-end" => $this->endTimeSpace, "alphaStart" => "1.0", "alphaEnd" => "0.0", "duration" => "1.5" ) );
		$filter2 = $this->createNode ( "filter", array ("type" => "distancemap", "distanceWidth" => "40.0" ) );
		$filter3 = $this->createNode ( "filter", array ("type" => "distancecolor", "distanceWidth" => "40.0", "color" => "#0000ff", "strokeColor" => "#ffffff", "strokeOpacity" => "1.0", "strokeWidth" => "0.02", "dropShadowColor" => "#000000", "dropShadowOpacity" => "1.0", "dropShadowBlurWidth" => "0.9", "dropShadowPosition" => "0.01,-0.01" ) );
		$key = $this->createNode ( "key", array ("time" => "0.0", "pos" => "0,-0.86,0" ) );
		$animator2 = $this->createNode ( "animator", array ("type" => "custom" ), array ($key ) );
		$footerText2 = $this->createNode ( "text", array ("type" => "zone", "vector" => "true", "fontsize" => "16", "stretch" => "condensed", "face" => "times", "align" => "center,center" ), array ($filter, $filter2, $filter3, $animator2 ), $this->link );
		$this->appendChild ( $this->stackNode, $footerText2 );
		
		// Add bg audio
		$audio = $this->createNode ( "audio", array ("filename" => $this->bgAudio, "duration" => "..", "fadeout" => "5", "volume" => "0.20" ) );
		$this->appendChild ( $this->stackNode, $audio );
		
		$stacks = array ();
		
		// Add Sequence of description text
		foreach ( $this->description as $index => $line ) {
			// Convert line to regular text
			//$line = mb_convert_encoding ( $line, "UTF-8" );
			$line = htmlspecialchars_decode ( $line );
			$line = html_entity_decode ( $line );
			
			$textAudioStart = "2.0";
			$textAudioEnd = "0.0";
			if ($index == 0) {
				$textAudioStart = $this->startTimeSpace;
			} else if ($index == count ( $this->description ) - 1) {
				$textAudioEnd = "1.0";
			}
			
			$maxTexLines = 7;
			
			$reference = 'AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA&#10;';
			$textPosition = "-1.5,0.5,0";
			
			if (isset ( $this->productImages [$index] ) && strlen ( $this->productImages [$index] ) > 0) {
				$reference = 'AAAAAAAAAAAAAAAA&#10;';
				$textPosition = "-1.5,0.55,0";
		}
			
			// Increase the reference text to fit $maxTextLines
			$textBoundary = "";
			for($i = 0; $i < $maxTexLines; $i ++) {
				$textBoundary .= $reference;
			}
			$reference = $textBoundary;
			
			// Text
			$animator = $this->createNode ( "animator", array ("type" => "slide-in", "direction" => "left", "duration" => "2.0" ) );
			$animator2 = $this->createNode ( "animator", array ("type" => "slide-out", "direction" => "left", "margin-end" => "0.0", "duration" => "1.0" ) );
			$animator3 = $this->createNode ( "animator", array ("type" => "slide", "direction" => "left", "duration" => ".." ) );
			$filter = $this->createNode ( "filter", array ("type" => "alpha", "alphaStart" => "0.0", "alphaEnd" => "1.0", "duration" => "3.0" ) );
			$filter2 = $this->createNode ( "filter", array ("type" => "alpha", "margin-end" => "0.0", "alphaStart" => "1.0", "alphaEnd" => "0.0", "duration" => "1.5" ) );
			$descriptionText = $this->createNode ( "text", array ("type" => "advanced", "weight" => "bold", "style" => "italic", "fontsize" => "14", "stretch" => "condensed", "face" => "times", "reference" => $reference ), array ($animator, $animator2, $animator3, $filter, $filter2 ), $line );
			
			// Text Positioner
			$key = $this->createNode ( "key", array ("time" => "0.0", "pos" => $textPosition ) );
			$animator4 = $this->createNode ( "animator", array ("type" => "custom" ), array ($key ) );
			
			// Text as audio
			$audio = $this->createNode ( "audio", array ("voice" => "neospeech:" . $this->audioVoice, "margin-start" => $textAudioStart, "margin-end" => $textAudioEnd ), array (), $line );
			
			$childNodes = array ($descriptionText, $animator4 );
			
			if (isset ( $this->productImages [$index] )) {
				// Add Product Image
				$image = $this->createNode ( "image", array ("filename" => $this->productImages [$index] ) );
				$ianimator = $this->createNode ( "animator", array ("type" => "slide-in", "direction" => "up", "duration" => "2.0" ) );
				$ianimator2 = $this->createNode ( "animator", array ("type" => "slide-out", "direction" => "up", "margin-end" => "0.0", "duration" => "2.0" ) );
				$ifilter = $this->createNode ( "filter", array ("type" => "alpha", "alphaStart" => "0.0", "alphaEnd" => "1.0", "duration" => "1.5" ) );
				$ifilter2 = $this->createNode ( "filter", array ("type" => "alpha", "margin-end" => "0.0", "alphaStart" => "1.0", "alphaEnd" => "0.0", "duration" => "2.0" ) );
				$effect = $this->createNode ( "effect", array ("type" => "none" ), array ($image, $ianimator, $ianimator2, $ifilter, $ifilter2 ) );
				$overlay = $this->createNode ( "overlay", array ("width" => "0.4", "top" => "0.5", "height" => "0.50", "left" => "0.95" ), array ($effect ) );
				$childNodes [] = $overlay;
			}
			
			$childNodes [] = $audio;
			
			$stacks [] = $this->createNode ( "stack", array (), $childNodes );
		
		}
		
		// Add transition into final stack
		//$stacks [] = $this->createNode ( "transition", array ("type" => "crossfade", "duration" => "5" ) );
		

		// Add Final sequence stack
		$filter2 = $this->createNode ( "filter", array ("type" => "distancemap", "distanceWidth" => "40.0" ) );
		$filter3 = $this->createNode ( "filter", array ("type" => "distancecolor", "distanceWidth" => "40.0", "color" => "#0000ff", "strokeColor" => "#ffffff", "strokeOpacity" => "1.0", "strokeWidth" => "0.02", "dropShadowColor" => "#000000", "dropShadowOpacity" => "1.0", "dropShadowBlurWidth" => "0.9", "dropShadowPosition" => "0.01,-0.01" ) );
		$key = $this->createNode ( "key", array ("time" => "0.0", "pos" => "0,0.0,0" ) );
		$animator2 = $this->createNode ( "animator", array ("type" => "custom" ), array ($key ) );
		$footerText3 = $this->createNode ( "text", array ("type" => "zone", "vector" => "true", "fontsize" => "18", "stretch" => "condensed", "face" => "times", "align" => "center,center" ), array ($filter2, $filter3, $animator2 ), $this->link );
		$data = str_split ( $this->pid );
		//$pid = implode ( " ", $data );
		$pid = implode ( ".", $data );
		$endText = "Find Out More Now at www.ChrisQueen.com/CB/" . $pid;
		$audio = $this->createNode ( "audio", array ("voice" => "neospeech:" . $this->audioVoice, "margin-start" => "0.0" ), array (), $endText );
		$stacks [] = $this->createNode ( "stack", array ("duration" => $this->endTimeSpace ), array ($footerText3, $audio ) );
		
		// Add sequence with the stack nodes
		$sequence = $this->createNode ( "sequence", array (), $stacks );
		$this->appendChild ( $this->stackNode, $sequence );
	
	}
	
	function createNode($nodeName, array $attributes = array(), array $childNodes = array(), $value = null) {
		$newNode = $this->doc->createElement ( $nodeName, $value );
		foreach ( $attributes as $name => $value ) {
			$newNode->setAttribute ( $name, $value );
		}
		$this->appendChildren ( $newNode, $childNodes );
		return $newNode;
	}
	
	function appendChild($parent, $child) {
		return $parent->appendChild ( $child );
	}
	
	function appendChildren($parent, array $children) {
		foreach ( $children as $child ) {
			$this->appendChild ( $parent, $child );
		}
	}
	
	function addNodeValue($node, $value) {
		$node->nodeValue = $value;
	}

}

?>