<?php
	
	require_once "objects/Node.php";
	require_once "functions/common_functions.php";
	require_once "functions/repo_scan_functions.php";
	require_once "functions/database_functions.php";
	require_once 'vendor/autoload.php';


	use GraphAware\Neo4j\Client\ClientBuilder;



	$repoPath = "/home/thoums/Documents/www/PHP/X_test_repo";
	$repoName = "X_test_repo";


	$timestamp_start = microtime(true); //Just to mesure running time
	
	
	
	
	//Get array of every file in repo
	try {
		$files = scanDirectory($repoPath, array());
	}
	catch (Exception $e) {
		echo "Exception while scanning directory : ".$e->getMessage();
	}
	$files = keepSpecificTypesOnly($files, array(".php"));
	
	
	
	
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
		$node->analyseFile();
		
		/*
		//Debuging 
		echo "<br>".$file."<br>";
		displayArray($node->getFeatures());
		displayArray($node->getIncludes());
		displayArray($node->getRequires());
		displayArray($node->getNamespaces());
		displayArray($node->getUses());
		*/


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
		echo $node->getPath()."<br>";

		$includeQuery = $node->generateIncludeRelationQuery();
		if ($includeQuery) {
			echo $includeQuery."<br>";
			runQuery($client, $includeQuery);
		}

		$requireQuery = $node->generateRequireRelationQuery();
		if ($requireQuery) {
			echo $requireQuery."<br>";
			runQuery($client, $requireQuery);
		}

		echo "<br><br>";
	}




	echo "<br>Done.<br>";


	echo "<br><br>";
	echo 'Running time : ' .(microtime(true) - $timestamp_start). 's.';


?>