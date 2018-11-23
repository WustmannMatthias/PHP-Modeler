<?php

/**
	Allows to run a query to a connected neo4j database, and catch potential Exceptions
	@param client is the link to the database
	@param query is the query to send
	@return is the result of the query
*/
function runQuery($client, $query) {
	try {
		$result = $client->run($query);
	}
	catch (GraphAware\Bolt\Exception\IOException $e) {
		echo "<b>GraphAware\Bolt\Exception\IOException</b> : ".$e->getMessage()."<br>";
	}
	catch (GraphAware\Neo4j\Client\Exception\Neo4jException $e) {
		echo "<b>GraphAware\Neo4j\Client\Exception\Neo4jException</b> : ".$e->getMessage()
			."<br>";
	}
	return $result;
}


?>