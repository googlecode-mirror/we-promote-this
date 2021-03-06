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
		WHERE p.id not in (Select pt.pid from post as pt where pt.posted=1)
		)
		UNION ALL
		(
		SELECT pt.pid AS id
		FROM post as pt
		WHERE pt.posted=1
		)
		) as grow
		JOIN products as pr ON grow.id=pr.id
		JOIN keywords as k ON k.id=grow.id
		JOIN users as u ON u.category=pr.category
		WHERE k.words!='[\"{BLANK}\"]' AND CHAR_LENGTH(k.words)>4 AND CHAR_LENGTH(pr.description)>5
		";
		$query .= "AND pr.gravity <40
        AND pr.gravity >0
        ";
		$query .= " GROUP BY (grow.id)
		";

		//$query.="ORDER BY COUNT(grow.id) ASC, pr.gravity DESC, pr.commission DESC, pr.popularityrank DESC, CHAR_LENGTH(pr.description) DESC, RAND()";
		$query .= "ORDER BY COUNT(grow.id) ASC, pr.gravity ASC, pr.initialearningspersale DESC, pr.averageearningspersale DESC, pr.popularityrank DESC, CHAR_LENGTH(pr.description) DESC, RAND() ";

		$query .= "LIMIT 1";

		$query2 = "Select coalesce(
                  (SELECT p.id as pid from 
                  products as p 
                  JOIN keywords as k ON k.id=p.id 
                  JOIN users as u ON u.category=p.category
                  WHERE
                  p.id not in (Select pt.pid from post as pt where pt.posted=1 AND pt.user_id=u.id) AND
                  k.id is not null AND k.words!='[\"{BLANK}\"]' AND CHAR_LENGTH(k.words)>4 AND CHAR_LENGTH(p.description)>5 
                  group by p.id
                  order by rand()
                  LIMIT 1)
                  ,($query)) as pid
            ";

		if (isset($_REQUEST['debug'])) {
			echo("Query 1: " . $query . "<br><br>");
			die("Query 2: " . $query2 . "<br>");
		}

		$index = rand(0, 100);
		if ($index <= 75) {
			$query = $query2;
		}

		$result = $this -> runQuery($query, $this -> getDBConnection() -> getDBConnection());
		$row = $result -> fetch_assoc();
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