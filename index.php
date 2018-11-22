<?php
	/*******************************************************************************
	********************************************************************************
	******************************** INITIALISATION ********************************
	********************************************************************************
	*******************************************************************************/

	error_reporting(E_ALL);
	$timestamp_full = microtime(true);

	require_once "objects/Node.php";
	require_once "functions/common_functions.php";
	require_once "functions/repo_scan_functions.php";
	require_once "functions/database_functions.php";
	require_once "vendor/autoload.php";

	use GraphAware\Neo4j\Client\ClientBuilder;

	//$repoToTest = "/home/wustmann/Documents/invoicing";
	$repoToTest = X_TEST_REPO_PATH;
	





	/*******************************************************************************
	********************************************************************************
	**************************** REPOSITORY * SCANNING *****************************
	********************************************************************************
	*******************************************************************************/
	
	//Get array of every file in repo
	$timestamp_directory = microtime(true);
	try {
		$files = getDirContent($repoToTest);
	}
	catch (Exception $e) {
		echo "Exception while scanning directory : ".$e->getMessage();
		exit;
	}
	$files = keepSpecificTypesOnly($files, array('.php', '.inc'));
	$repoName = getRepoName($repoToTest);
	//$repoName = "Pricer2016Q2";
	$timestamp_directory = microtime(true) - $timestamp_directory;
	




	
	
	/************************* DATABASE * INITIALISATION **************************/
	$client = ClientBuilder::create()
	    ->addConnection('bolt', 'bolt://neo4j:password@localhost:7687')
	    ->build();
	runQuery($client, "MATCH (n)-[r]->(n2) DELETE r, n, n2");
	runQuery($client, "MATCH (n) DELETE n");
	






	/*******************************************************************************
	********************************************************************************
	****************************** FIRST * ANALYSIS ********************************
	********************************************************************************
	*******************************************************************************/
	/**
		STEP 1 : Analyse every file, store analysis, and send node in database
		After this first step, every file, namespace, and feature will be represented
		in the modeling. However, links between files won't be.
	*/
	echo "<h2>STEP 1 ANALYSE</h2>";
	$timestamp_analyse = microtime(true);
	$nodes = array();
	foreach ($files as $file) {
		//Create Node object for each file and analyse it
		$node = new Node($file, $repoName);
		echo "<b>Analysing file ".$node->getPath()."</b><br>";
		$node->analyseFile();

		//Send node in database
		$query = $node->generateUploadQuery();
		runQuery($client, $query);
		
		//Save the object
		array_push($nodes, $node);
		echo "<br>";
	}
	echo "<br>Done.<br><br>";
	$timestamp_analyse = microtime(true) - $timestamp_analyse;







	/*******************************************************************************
	********************************************************************************
	****************** STORE * DEPENDENCIES * IN * DATABASE ************************
	********************************************************************************
	*******************************************************************************/
	/**
		STEP 2 : Read informations stored in every node, find dependencies, and
		create relationsships in database.
	*/
	echo "<h2>STEP 2 UPLOAD DEPENDENCIES</h2>";
	$timestamp_dependencies = microtime(true);
	foreach ($nodes as $node) {
		//Send include relations in database
		$includeQuery = $node->generateIncludeRelationQuery();
		if ($includeQuery) {
			runQuery($client, $includeQuery);
		}

		//Send require relations in database
		$requireQuery = $node->generateRequireRelationQuery();
		if ($requireQuery) {
			runQuery($client, $requireQuery);
		}

		//Send use relations in database
		$useQuery = $node->generateUseRelationQuery();
		if ($useQuery) {
			runQuery($client, $useQuery);
		}
	}
	echo "<br><br>";
	$timestamp_dependencies = microtime(true) - $timestamp_dependencies;







	/*******************************************************************************
	********************************************************************************
	*************************** DISPLAY * PERFORMANCES *****************************
	********************************************************************************
	*******************************************************************************/
	$timestamp_full = microtime(true) - $timestamp_full;

	echo "<h2>PERFORMANCES</h2>";
	echo "Time to load repository : $timestamp_directory s<br>";
	echo "Time analyse repository : $timestamp_analyse s<br>";
	echo "Time upload dependencies : $timestamp_dependencies s<br>";
	echo "Script full running time : $timestamp_full s<br>";

?>