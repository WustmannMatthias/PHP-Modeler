<?php


	require_once "../functions/database_functions.php";
	require_once "../functions/common_functions.php";
	require_once '../vendor/autoload.php';


	use GraphAware\Neo4j\Client\ClientBuilder;


	$timestamp_start = microtime(true); //Just to mesure running time

	//Connexion to database
	$client = ClientBuilder::create()
	    ->addConnection('bolt', 'bolt://neo4j:password@localhost:7687')
	    ->build();
	
	$project = "Pricer2016Q2";
	$iteration = "test_no_inc";

	$query = "MATCH (p:Project {name: '$project'}) 
			  MATCH (i:Iteration {name: '$iteration'})-[:IS_ITERATION_OF]->(p)
			  MATCH (files)-[:BELONGS_TO]->(i)
			  MATCH (files)-[:IS_INCLUDED_IN|:IS_REQUIRED_IN|:IS_USED_BY|:DECLARES*0..6]->(eFiles:File)
			  WITH eFiles
			  ORDER BY eFiles.path ASC 
			  RETURN DISTINCT eFiles.path AS files
			 ";

	
	$result = runQuery($client, $query);

	
	$paths = array();
	foreach ($result->records() as $record) {
		array_push($paths, $record->value("files"));
	}

	echo "\n\n";

	$regex = '/Pricer2016Q2\/intranet\/(?P<feature>[^\/]+\.php)$/';
	$features = array();

	foreach ($paths as $path) {
		if (preg_match($regex, $path, $matches)) {
			array_push($features, $matches['feature']);
		}
	}

	displayArray($features);


	echo "\nDone.\n";


	echo "\n\n";
	echo 'Running time : ' .(microtime(true) - $timestamp_start). 's.';
?>
