<?php


	require_once "functions/database_functions.php";
	require_once 'vendor/autoload.php';


	use GraphAware\Neo4j\Client\ClientBuilder;


	$timestamp_start = microtime(true); //Just to mesure running time

	//Connexion to database
	$client = ClientBuilder::create()
	    ->addConnection('bolt', 'bolt://neo4j:password@localhost:7687')
	    ->build();
	
	$query = "MATCH (n:File) WHERE NOT (n)-[:IS_INCLUDED_IN]->() 
							   AND NOT (n)-[:IS_REQUIRED_IN]->()
							   AND NOT (n)-[:IS_USED_BY]	->()
							   AND NOT (n)-[:DECLARES]		->(:Namespace)
							   		-[:IS_USED_BY]->(:File)
			  RETURN n.path as path";

	
	$result = runQuery($client, $query);

	echo "Here is the list of the main files of the programm. Thoses files aren't 
			included or required in any others, and must be annotated to tell which
			feature(s) they impact.";
	echo "<br><br>";

	foreach ($result->records() as $record) {
		echo $record->value("path");
		echo "<br>";
	}



	echo "<br>Done.<br>";


	echo "<br><br>";
	echo 'Running time : ' .(microtime(true) - $timestamp_start). 's.';
?>