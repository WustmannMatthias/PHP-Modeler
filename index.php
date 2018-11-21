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

	$repoToTest = PRICER_PATH;
	//$repoToTest = X_TEST_REPO_PATH;
	


	
	//Get array of every file in repo
	try {
		$files = getDirContents($repoToTest);
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
	echo "<h1>STEP 1 ANALYSE</h1><br>";
	foreach ($files as $file) {
		//Create Node object for each file and analyse it
		$node = new Node($file, $repoName);
		
		//echo $node->getPath();
		//echo "<br>";
		$node->analyseFile();
		//echo "<br>";
		
		
		//Debuging 
		/*
		echo "Features : <br>";
		displayArray($node->getFeatures());
		echo "Includes : <br>";
		displayArray($node->getIncludes());
		echo "Requires : <br>";
		displayArray($node->getRequires());
		*/
		//echo "Namespaces : <br>";
		//displayArray($node->getNamespaces());
		//echo "Uses : <br>";
		//displayArray($node->getUses());
		
		


		//Send node in database
		$query = $node->generateUploadQuery();
		//echo "<br>".$query."<br>";
		runQuery($client, $query);
		
		//Save the object
		array_push($nodes, $node);
	}


	/**
		STEP 2 : Read informations stored about every node, find dependencies, and
		create relationsships in database.
	*/
	echo "<h2>".'STEP 1 RUNNING TIME : ' .(microtime(true) - $timestamp_start). 's.';
	echo "<br><br><br><br>";
	echo "<h1>STEP 2 UPLOAD DEPENDENCIES</h1><br>";
	foreach ($nodes as $node) {
		echo $node->getPath()."<br>";

		//Send include relations in database
		$includeQuery = $node->generateIncludeRelationQuery();
		if ($includeQuery) {
			//echo $includeQuery."<br>";
			runQuery($client, $includeQuery);
		}

		//Send require relations in database
		$requireQuery = $node->generateRequireRelationQuery();
		if ($requireQuery) {
			//echo $requireQuery."<br>";
			runQuery($client, $requireQuery);
		}

		/*
		SEND USE RELATIONS IN DATABASE
		*/
		if (sizeof($node->getUses()) == 0) {
			continue;
		}
		$uses = $node->prepareUses(); // array(namespace => className)
		displayArray($uses);

		$useRelation = "IS_USED_BY";

		$path 		= $node->getPathFromRepo($node->getPath(), $node->getRepoName());
		$queryBegin = "MATCH (f:File {path: '".$path."'}) " ;
		$queryEnd	= "";

		$counter = 0;
		foreach ($uses as $namespace => $className) { //For each USE found in the file
			$counter ++;
			$tempQuery = "MATCH (n:Namespace {name: '$namespace'}) 
							RETURN n.name as namespace";
			$tempResult = runQuery($client, $tempQuery);

			if (sizeof($tempResult->records()) === 1) { // Namespace already exists
				$queryBegin	.= "MATCH (n".$counter.":Namespace {name: '$namespace'}) ";
				$queryEnd	.= "CREATE (n".$counter.")-[:$useRelation]->(f) ";
			}
			elseif (sizeof($tempResult->records()) === 0) { // Namespace doesnt exist
				$queryEnd	.= "CREATE (n".$counter.":Namespace {name: '$namespace', 
								non_declared: 'True'})-[:$useRelation]->(f) ";
			}
			else { //(sizeof($tempResult->records()) > 1) : Should'nt happend
				echo "WTF can't be true :o<br>";
				continue;
			}
		}

		$useQuery = $queryBegin.$queryEnd;
		if ($useQuery != $queryBegin) {
			echo $useQuery."<br>";
			runQuery($client, $useQuery);
		}

		echo "<br><br>";
	}


	echo "<br>Done.";


	echo "<br><br>";
	echo "<h2>".'FULL RUNNING TIME : ' .(microtime(true) - $timestamp_start). 's.<br>';


?>