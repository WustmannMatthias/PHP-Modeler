<?php
	error_reporting(E_ALL);

	require_once "objects/Node.php";
	require_once "functions/common_functions.php";
	require_once "functions/repo_scan_functions.php";
	require_once "functions/database_functions.php";
	require_once "vendor/autoload.php";
	require_once "constants.php";


	use GraphAware\Neo4j\Client\ClientBuilder;



	$timestamp_start = microtime(true); //Just to mesure running time

	$repoToTest = PRICER_REALLY_SMALL_TEST_PATH;
	//$repoToTest = X_TEST_REPO_PATH;
	


	
	//Get array of every file in repo
	try {
		$files = scanDirectory($repoToTest, array());
	}
	catch (Exception $e) {
		echo "Exception while scanning directory : ".$e->getMessage();
		exit;
	}
	$files = keepSpecificTypesOnly($files, array('.php', '.inc'));
	$repoName = getRepoName($repoToTest);
	
	
	
	//Connexion to database + clear
	$client = ClientBuilder::create()
	    ->addConnection('bolt', 'bolt://neo4j:password@localhost:7687')
	    ->build();
	runQuery($client, "MATCH (n)-[r]->(n2) DELETE r, n, n2");
	runQuery($client, "MATCH (n) DELETE n");
	

	
	$nodes = array(); //just a container

	/**
		STEP 1 : Analyse every file, store analysis, and send node in database
		After this first step, every file have a representation in database, but have
		no link between eachother.
		However, links between files and features are modelised
	*/
	foreach ($files as $file) {
		//Create Node object for each file and analyse it
		$node = new Node($file, $repoName);
		
		echo $node->getPath();
		echo "<br>";
		$node->analyseFile();
		echo "<br>";
		
		
		//Debuging 
		echo "Features : <br>";
		displayArray($node->getFeatures());
		echo "Includes : <br>";
		displayArray($node->getIncludes());
		echo "Requires : <br>";
		displayArray($node->getRequires());
		echo "Namespaces : <br>";
		displayArray($node->getNamespaces());
		echo "Uses : <br>";
		displayArray($node->getUses());

		


		//Send node in database
		$query = $node->generateUploadQuery();
		runQuery($client, $query);

		//Save the object
		array_push($nodes, $node);
	}


	/**
		STEP 2 : Read informations stored about every node, find dependencies, and
		create relationsships in database.
	*/
	foreach ($nodes as $node) {
		$includeQuery = $node->generateIncludeRelationQuery();
		if ($includeQuery) {
			//echo $includeQuery."<br>";
			runQuery($client, $includeQuery);
		}

		$requireQuery = $node->generateRequireRelationQuery();
		if ($requireQuery) {
			//echo $requireQuery."<br>";
			runQuery($client, $requireQuery);
		}
	}


	echo "<br>Done.";


	echo "<br><br>";
	echo 'Running time : ' .(microtime(true) - $timestamp_start). 's.';


?>