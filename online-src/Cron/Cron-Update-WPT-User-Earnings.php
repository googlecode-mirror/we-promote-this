<?php
// Use Word Press Settings and use that database

require_once 'CronAbstract.php';
class CronUpdateWPTUserEarnings extends CronAbstract {
	function runCron() {
		$query = "SELECT user_id, meta_value AS api FROM wp_usermeta WHERE meta_key='clickbank_clerk_api_key' AND CHAR_LENGTH(meta_value)>0";
		$result = $this->getDBConnection()->queryWP ( $query );
		$valueString = "";
		while ( ($row = $result-> fetch_assoc()) ) {
			$userId = $row ["user_id"];
			$api = $row ["api"];
			$total = $this->getTotalSales ( $api );
            //$totalold = $this->getTotalSales_old ( $api );
            //echo("Total for $userId :".$total."<br>");
            //echo("Total for $userId (old):".$totalold."<br><br>");
            //echo("Total for made up user (old):".$this->getTotalSales ( 'totallymadeup' )."<br><br>");
			$valueString .= "(" . $userId . ",'cbearnings'," . $total . "),";
		}
		$this->getDBConnection()->queryWP ( "DELETE FROM wp_usermeta WHERE meta_key='cbearnings'" );
		$valueString = substr ( $valueString, 0, strlen ( $valueString ) - 1 );
		$insertQuery = "INSERT INTO wp_usermeta (user_id,meta_key,meta_value) VALUES" . $valueString;
		$this->getDBConnection()->queryWP ( $insertQuery );
		//echo ($insertQuery . "<br><br>");
		echo ("Users CB Earnings Updated: " . date ( "Y-m-d H:i:s A" ));
        
        if(isset($_REQUEST['debug'])){
            echo("<br>".str_replace('),', ')<br>', $valueString)."<br>");
        }
        
	}
    
    function getTotalSales($api) {
        $ch = curl_init ();
        $params = array ();
        $url = "https://api.clickbank.com/rest/1.2/quickstats/count";
        $currentMonth = date ( "m" );
        if(isset($_REQUEST['m'])){
            $currentMonth = $_REQUEST['m'];
        }
        $currentYear = date ( "Y" );
        if(isset($_REQUEST['y'])){
            $currentYear = $_REQUEST['y'];
        }
        $start = new DateTime ( $currentMonth . "/01/" . $currentYear, new DateTimeZone ( "America/New_York" ) );
        if (isset ( $start )) {
            $params [] = "startDate=" . $start->format ( "Y-m-d" );
        }
        if (count ( $params ) > 0) {
            $url .= "?" . implode ( "&", $params );
        }
        
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_HEADER, false );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $headerArray = array ("Accept: application/json", "Authorization: DEV-4C19764011D669F47A933DCD8C14BFD2214E:" . $api );
        curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headerArray );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        $result = curl_exec ( $ch );
        curl_setopt ( $ch, CURLOPT_HEADER, TRUE );
        curl_setopt ( $ch, CURLOPT_NOBODY, TRUE );
        $returnHeader = curl_getinfo ( $ch );
        curl_close ( $ch );
        
        //echo (var_dump ( $result )."<br><br>");
        if (stripos ( $result, 'Access is denied' ) !== false) {
            // Access was denied so return some large total so the user will have reached their limit since they didn't put in a correct api key
            return 1000000000;
        }
        
        $json = json_decode($result,true);
        //echo (var_dump ( $json )."<br><br>");
        $total = $json['accountData']['quickStats']['sale'];
        if(!isset($total)|| strlen($total)<=0){
            $total = 0;
        }
        return $total;
    }
	
	function getTotalSales_old($api, $next = 0) {
		$ch = curl_init ();
		$params = array ();
		$url = "https://api.clickbank.com/rest/1.2/orders/list";
		$currentMonth = date ( "m" );
		$lastDayofMonth = date ( "t" );
		$currentYear = date ( "Y" );
		$start = new DateTime ( $currentMonth . "/01/" . $currentYear, new DateTimeZone ( "America/New_York" ) );
		if (isset ( $start )) {
			$params [] = "startDate=" . $start->format ( "Y-m-d" );
		}
		$end = new DateTime ( $currentMonth . "/" . $lastDayofMonth . "/" . $currentYear, new DateTimeZone ( "America/New_York" ) );
		if (isset ( $end )) {
			$params [] = 'endDate=' . $end->format ( "Y-m-d" );
		}
		if (count ( $params ) > 0) {
			$url .= "?" . implode ( "&", $params );
		}
		
		curl_setopt ( $ch, CURLOPT_URL, $url );
		curl_setopt ( $ch, CURLOPT_HEADER, false );
		curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$headerArray = array ("Accept: application/xml", "Authorization: DEV-4C19764011D669F47A933DCD8C14BFD2214E:" . $api );
		if ($next != 0) {
			$headerArray [] = "Page: " . $next;
		}
		curl_setopt ( $ch, CURLOPT_HTTPHEADER, $headerArray );
		
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		$result = curl_exec ( $ch );
		curl_setopt ( $ch, CURLOPT_HEADER, TRUE );
		curl_setopt ( $ch, CURLOPT_NOBODY, TRUE );
		$returnHeader = curl_getinfo ( $ch );
		curl_close ( $ch );
		
		//echo (var_dump ( $result ));
		if (stripos ( $result, 'Access is denied' ) !== false) {
			// Access was denied so return some large total so the user will have reached their limit since they didn't put in a correct api key
			return 1000000000;
		}
		
		$dom = new DOMDocument ( );
		@$dom->loadXML ( $result );
		$xpath = new DOMXPath ( $dom );
		$data = $xpath->evaluate ( "//orderData" );
		$total = 0;
		foreach ( $data as $node ) {
			$amountData = $xpath->evaluate ( "accountAmount/text()", $node );
			$dateData = $xpath->evaluate ( "date/text()", $node );
			$typeData = $xpath->evaluate ( "txnType/text()", $node );
			$amount = $amountData->item ( 0 )->nodeValue;
			$date = $dateData->item ( 0 )->nodeValue;
			$type = $typeData->item ( 0 )->nodeValue;
			$total += $amount;
			//echo ("Date: $date \t Amount: $amount \t Type: $type<br>");
		}
		if (stripos ( $returnHeader ['http_code'], '206' ) !== FALSE) {
			$total += $this->getTotalSales_old ( $api, ++ $next );
		}
		return $total;
	}
}
new CronUpdateWPTUserEarnings ( );

?>