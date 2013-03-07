<?php

require_once 'CBUtils/CBAbstract.php';

class CBPadFilesWaiting extends CBAbstract {
	
	function constructClass() {
		
		//Change entries who have been processing for longer than an hour
		$query = "Update padfilesubmissions set progress='waiting' where updatedAt < (NOW() - INTERVAL 1 HOUR) AND progress='processing'";
		$this->getDBConnection()->queryDB ( $query );
		
		if (isset ( $_REQUEST ['finished'] )) {
			$query = "Update padfilesubmissions set progress='finished' where id=" . $_REQUEST ['finished'];
			//echo ("qf: $query<br>");
			echo ("Got It!!!");
			$this->getDBConnection()->queryDB ( $query );
		} else 

		if (isset ( $_REQUEST ['waiting'] )) {
			$query = "Update padfilesubmissions set progress='waiting' where id=" . $_REQUEST ['waiting'];
			//echo ("qf: $query<br>");
			echo ("Got It!!!");
			$this->getDBConnection()->queryDB ( $query );
		} else 

		{
			
			// Get 1 entry from queue
			$query = "Select * from padfilesubmissions where progress = 'waiting' order by updatedAt asc limit 1";
			$results = $this->getDBConnection()->queryDB ( $query );
			//echo("results:<br>");
			//var_dump($results);
			//echo("<br>");
			if (count ( $results ) == 0 || $results === false) {
				echo ('nothing');
			} else {
				$row = $results-> fetch_assoc();
				$id = $row ['id'];
				$padURL = $row ['padURL'];
				$fullSubmit = $row ['fullSubmit'];
				$fullSubmitString = '0';
				if ($fullSubmit == true) {
					$fullSubmitString = '1';
				}
				if (strlen ( $id ) > 0 && strlen ( $padURL ) > 0) {
					echo ("$id\n$padURL\n$fullSubmitString");
					
					// Update its state to processing
					$query = "UPDATE padfilesubmissions SET progress='processing' where id=" . $id;
					$this->getDBConnection()->queryDB ( $query );
				
				} else {
					echo ('nothing');
				}
			
			}
		
		}
	
	}

}

$obj = new CBPadFilesWaiting ( );

?>