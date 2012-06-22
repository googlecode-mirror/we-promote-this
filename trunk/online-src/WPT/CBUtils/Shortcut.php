<?php

class Shortcut {
	
	public $iconLocation;
	public $iconName;
	public $shortcutLocation;
	public $shortcutName;
	public $imageResuorce;
	
	function __construct($shortcutName, $tagetUrl, array $keywords, $outputFolder, $description = null, $domainSearch = null) {
		$shell = new COM ( 'WScript.Shell' );
		$linkName = $shortcutName . ".LNK";
		$this->shortcutName = $linkName;
		$link = $outputFolder . $linkName;
		$this->shortcutLocation = $link;
		$shortcut = $shell->createshortcut ( $link );
		$shortcut->targetpath = $tagetUrl;
		$shortcut->description = $description;
		$results = array ();
		//echo ("Keywords:<br>");
		//print_r ( $keywords );
		//echo ("<br>");
		if (isset ( $domainSearch )) {
			foreach ( $keywords as $keyword ) {
				$gresults = $this->googleImageResults ( $keyword, $domainSearch );
				//echo ("Google Image Results Count:" . count ( $gresults ) . "<br>");
				if (count ( $gresults ) > 0) {
					array_splice ( $results, count ( $results ), 0, $gresults );
				}
			}
		}
		
		if (count ( $results ) == 0) {
			foreach ( $keywords as $keyword ) {
				$gresults = $this->googleImageResults ( $keyword );
				//echo ("Google Image Results Count:" . count ( $gresults ) . "<br>");
				if (count ( $gresults ) > 0) {
					array_splice ( $results, count ( $results ), 0, $gresults );
				}
			
			}
		}
		//echo ("Image Results:<br>");
		//print_r ( $results );
		//echo ("<br>");
		

		//Set Icon Location
		$iconName = "Icon" . date ( "dmYHis" ) . ".ico";
		$this->iconName = $iconName;
		//echo ("Icon Name: $iconName<br>");
		$iconFolder = getenv ( 'programfiles' ) . '/' . $shortcutName . '/';
		$futureIconLocation = $iconFolder . $iconName;
		$iconLocation = $outputFolder . $iconName;
		$this->iconLocation = $iconLocation;
		
		$iconCreated = false;
		do {
			try {
				$index = array_rand ( $results );
				$slectedImage = $results [$index];
				$imageLocation = $slectedImage ['ImgUrl'];
				unset ( $results [$index] );
				
				//Create Icon
				list ( $width, $height ) = getimagesize ( $imageLocation );
				$newwidth = 32;
				$newheight = 32;
				$img = imagecreatetruecolor ( $newwidth, $newheight ); // Need to create 32x32 icon
				$source = imagecreatefromjpeg ( $imageLocation );
				// Resize
				imagecopyresized ( $img, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height );
				$this->imageResuorce = $img;
				$this->imagebmp ( $img, $iconLocation );
				$iconCreated = true;
			} catch ( Exception $e ) {
				
				echo ("Error converting image to icon: <img src='$imageLocation'><br>$imageLocation<br>");
			}
		
		} while ( ! $iconCreated && count ( $results ) > 0 );
		
		echo ("Selected Image URL: <img src='$imageLocation'><br>$imageLocation<br>");
		echo ("Icon Location: $iconLocation<br>");
		
		//$shortcut->iconlocation = $iconLocation;
		$shortcut->iconlocation = $futureIconLocation;
		//$shortcut->iconlocation = './'.$iconName;
		$shortcut->save ();
	}
	
	function __destruct() {
		// Free from memory
		ImageDestroy ( $this->imageResuorce );
	}
	
	function get_web_page($url) {
		// This example request includes an optional API key which you will need to
		// remove or replace with your own key.
		// Read more about why it's useful to have an API key.
		// The request also includes the userip parameter which provides the end
		// user's IP address. Doing so will help distinguish this legitimate
		// server-side traffic from traffic which doesn't come from an end-user.
		// sendRequest
		// note how referer is set manually
		$options = array (CURLOPT_RETURNTRANSFER => true, // return web page
CURLOPT_FOLLOWLOCATION => true, // follow redirects
CURLOPT_ENCODING => "", // handle all encodings
CURLOPT_REFERER => "www.chrisqueen.com", // set referer on redirect
CURLOPT_CONNECTTIMEOUT => 120, // timeout on connect
CURLOPT_TIMEOUT => 120, // timeout on response
CURLOPT_MAXREDIRS => 10 ); // stop after 10 redirects
		$ch = curl_init ( $url );
		curl_setopt_array ( $ch, $options );
		
		$body = curl_exec ( $ch );
		curl_close ( $ch );
		
		//echo ("Body: <br>");
		//var_dump ( $body );
		//echo ("<br>");
		

		// now, process the JSON string
		$json = json_decode ( $body, true );
		// now have some fun with the results...
		return $json ['responseData'] ['results'];
	}
	
	function googleImageResults($query = '', $domain = null, $safe = 'off', $dc = "ajax.googleapis.com/ajax/services/search/images", $start = 0) {
		$query = urlencode ( $query );
		//echo ("Getting images for keyword: $query<br>");
		$results = array ();
		$ip_addr = getenv ( REMOTE_ADDR );
		$icon = null;
		if (isset ( $domain )) {
			$icon = "icon";
		}
		$url = sprintf ( "http://%s?q=%s&as_sitesearch=%s&hl=en&rsz=8&as_filetype=jpg&imgc=color&imgsz=%s&safe=%s&v=1.0&key=%s&userip=%s&start=%s", $dc, urlencode ( $query ), $domain, $icon, $safe, googleimagesearchapi, $ip_addr, $start );
		//echo ( "URL: $url<br>" );
		$gresults = $this->get_web_page ( $url );
		foreach ( $gresults as $g ) {
			$results [] = array ('ImgUrl' => $g ['url'] );
		}
		return $results;
	}
	
	/* Create a little-endian 16-bit integer (WORD) */
	function int_to_word($w) {
		$A = ((( int ) $w & 0x00FF) >> 0);
		$B = ((( int ) $w & 0xFF00) >> 8);
		return chr ( $A ) . chr ( $B );
	}
	
	/* Create a little-endian 32-bit integer (DWORD) */
	function int_to_dword($d) {
		$A = ((( int ) $d & 0x000000FF) >> 0);
		$B = ((( int ) $d & 0x0000FF00) >> 8);
		$C = ((( int ) $d & 0x00FF0000) >> 16);
		$D = ((( int ) $d & 0xFF000000) >> 24);
		return chr ( $A ) . chr ( $B ) . chr ( $C ) . chr ( $D );
	}
	
	function inttobyte($val) {
		$byte = array ();
		$byte [0] = ( int ) $val;
		$byte [1] = ( int ) ($val >> 8);
		$byte [2] = ( int ) ($val >> 16);
		$byte [3] = ( int ) ($val >> 24);
		return $byte;
	}
	
	function imagebmp($img, $file = "", $RLE = 0) {
		
		$ColorCount = imagecolorstotal ( $img );
		
		$Transparent = imagecolortransparent ( $img );
		$IsTransparent = $Transparent != - 1;
		
		if ($IsTransparent)
			$ColorCount --;
		
		if ($ColorCount == 0) {
			$ColorCount = 0;
			$BitCount = 24;
		}
		;
		if (($ColorCount > 0) and ($ColorCount <= 2)) {
			$ColorCount = 2;
			$BitCount = 1;
		}
		;
		if (($ColorCount > 2) and ($ColorCount <= 16)) {
			$ColorCount = 16;
			$BitCount = 4;
		}
		;
		if (($ColorCount > 16) and ($ColorCount <= 256)) {
			$ColorCount = 0;
			$BitCount = 8;
		}
		;
		
		$Width = imagesx ( $img );
		$Height = imagesy ( $img );
		
		$Zbytek = (4 - ($Width / (8 / $BitCount)) % 4) % 4;
		
		$palsize = 0;
		
		if ($BitCount < 24)
			$palsize = pow ( 2, $BitCount ) * 4;
		
		$size = (floor ( $Width / (8 / $BitCount) ) + $Zbytek) * $Height + 54;
		$size += $palsize;
		$offset = 54 + $palsize;
		
		// Bitmap File Header
		$ret = 'BM'; // header (2b)
		

		$ret .= $this->int_to_dword ( $size ); // size of file (4b)
		$ret .= $this->int_to_dword ( 0 ); // reserved (4b)
		$ret .= $this->int_to_dword ( $offset ); // byte location in the file which is first byte of IMAGE (4b)
		// Bitmap Info Header
		$ret .= $this->int_to_dword ( 40 ); // Size of BITMAPINFOHEADER (4b)
		$ret .= $this->int_to_dword ( $Width ); // width of bitmap (4b)
		$ret .= $this->int_to_dword ( $Height ); // height of bitmap (4b)
		$ret .= $this->int_to_word ( 1 ); // biPlanes = 1 (2b)
		$ret .= $this->int_to_word ( $BitCount ); // biBitCount = {1 (mono) or 4 (16 clr ) or 8 (256 clr) or 24 (16 Mil)} (2b)
		$ret .= $this->int_to_dword ( $RLE ); // RLE COMPRESSION (4b)
		$ret .= $this->int_to_dword ( 0 ); // width x height (4b)
		$ret .= $this->int_to_dword ( 0 ); // biXPelsPerMeter (4b)
		$ret .= $this->int_to_dword ( 0 ); // biYPelsPerMeter (4b)
		$ret .= $this->int_to_dword ( 0 ); // Number of palettes used (4b)
		$ret .= $this->int_to_dword ( 0 ); // Number of important colour (4b)
		// image data
		

		$CC = $ColorCount;
		$sl1 = strlen ( $ret );
		if ($CC == 0)
			$CC = 256;
		if ($BitCount < 24) {
			$ColorTotal = imagecolorstotal ( $img );
			if ($IsTransparent)
				$ColorTotal --;
			
			for($p = 0; $p < $ColorTotal; $p ++) {
				$color = imagecolorsforindex ( $img, $p );
				$ret .= $this->inttobyte ( $color ["blue"] );
				$ret .= $this->inttobyte ( $color ["green"] );
				$ret .= $this->inttobyte ( $color ["red"] );
				$ret .= $this->inttobyte ( 0 ); //RESERVED
			}
			
			$CT = $ColorTotal;
			for($p = $ColorTotal; $p < $CC; $p ++) {
				$ret .= $this->inttobyte ( 0 );
				$ret .= $this->inttobyte ( 0 );
				$ret .= $this->inttobyte ( 0 );
				$ret .= $this->inttobyte ( 0 ); //RESERVED
			}
		}
		
		if ($BitCount <= 8) {
			
			for($y = $Height - 1; $y >= 0; $y --) {
				$bWrite = "";
				for($x = 0; $x < $Width; $x ++) {
					$color = imagecolorat ( $img, $x, $y );
					$bWrite .= decbinx ( $color, $BitCount );
					if (strlen ( $bWrite ) == 8) {
						$retd .= $this->inttobyte ( bindec ( $bWrite ) );
						$bWrite = "";
					}
					;
				}
				;
				
				if ((strlen ( $bWrite ) < 8) and (strlen ( $bWrite ) != 0)) {
					$sl = strlen ( $bWrite );
					for($t = 0; $t < 8 - $sl; $t ++)
						$sl .= "0";
					$retd .= $this->inttobyte ( bindec ( $bWrite ) );
				}
				;
				for($z = 0; $z < $Zbytek; $z ++)
					$retd .= $this->inttobyte ( 0 );
			}
			;
		}
		;
		
		if (($RLE == 1) and ($BitCount == 8)) {
			for($t = 0; $t < strlen ( $retd ); $t += 4) {
				if ($t != 0)
					if (($t) % $Width == 0)
						$ret .= chr ( 0 ) . chr ( 0 );
				
				if (($t + 5) % $Width == 0) {
					$ret .= chr ( 0 ) . chr ( 5 ) . substr ( $retd, $t, 5 ) . chr ( 0 );
					$t += 1;
				}
				if (($t + 6) % $Width == 0) {
					$ret .= chr ( 0 ) . chr ( 6 ) . substr ( $retd, $t, 6 );
					$t += 2;
				} else {
					$ret .= chr ( 0 ) . chr ( 4 ) . substr ( $retd, $t, 4 );
				}
				;
			}
			;
			$ret .= chr ( 0 ) . chr ( 1 );
		} else {
			$ret .= $retd;
		}
		;
		
		if ($BitCount == 24) {
			for($z = 0; $z < $Zbytek; $z ++)
				$Dopl .= chr ( 0 );
			
			for($y = $Height - 1; $y >= 0; $y --) {
				for($x = 0; $x < $Width; $x ++) {
					$color = imagecolorsforindex ( $img, ImageColorAt ( $img, $x, $y ) );
					$ret .= chr ( $color ["blue"] ) . chr ( $color ["green"] ) . chr ( $color ["red"] );
				}
				$ret .= $Dopl;
			}
		
		}
		
		if ($file != "") {
			$r = ($f = fopen ( $file, "w" ));
			$r = $r and fwrite ( $f, $ret );
			$r = $r and fclose ( $f );
			return $r;
		} else {
			echo $ret;
		}
	
	}
}
?>
