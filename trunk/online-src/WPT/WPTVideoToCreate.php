<?php

require_once ("CBUtils/CBAbstract.php");

class WPTVVideoToCreate extends CBAbstract {

	function constructClass() {

		// Query Database and decide what video to create

		$query = "SELECT grow.id AS pid
		FROM
		((
		SELECT p.id
		FROM products as p
		LEFT JOIN post as pt ON pt.pid=p.id
		WHERE pt.pid is null
		)
		UNION ALL
		(
		SELECT pt.pid AS id
		FROM post as pt
		WHERE pt.posted=1
		)
		) as grow
		LEFT JOIN products as pr ON grow.id=pr.id
		LEFT JOIN keywords as k ON k.id=grow.id
		WHERE CHAR_LENGTH(k.words)>4 AND CHAR_LENGTH(pr.description)>5
		GROUP BY (grow.id)
		
		ORDER BY COUNT(grow.id) ASC, pr.gravity DESC, pr.commission DESC, pr.popularityrank DESC, CHAR_LENGTH(pr.description) DESC, RAND()
		LIMIT 1
		";
		$index = rand(0, 50);
		if ($index >= 25) {
			$query = "SELECT p.id as pid from products as p order by rand()";
		}
		$result = mysql_query($query);
		$row = mysql_fetch_assoc($result);
		$pid = $row["pid"];
		//$pid = "ETVCORP";
		echo($pid);
	}

	function __destruct() {
		parent::__destruct();
		/*
		 $logFile = get_class ( $this ) . "_logfile.html";
		 $f = fopen ( $logFile, "w" );
		 fwrite ( $f, $this->getOutputContent() );
		 fclose ( $f );
		 exec ( "start " . $logFile );
		 */
	}

}

$wpe = new WPTVVideoToCreate();
?>