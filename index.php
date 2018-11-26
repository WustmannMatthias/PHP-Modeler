<?php
	/*******************************************************************************
	********************************************************************************
	******************************** INITIALISATION ********************************
	********************************************************************************
	*******************************************************************************/

	error_reporting(E_ALL);
	$timestamp_full = microtime(true);

	require_once "vendor/autoload.php";
	
	use GraphAware\Neo4j\Client\ClientBuilder;

	require_once "objects/Node.php";

	require_once "functions/common_functions.php";
	require_once "functions/repo_scan_functions.php";
	require_once "functions/database_functions.php";
	require_once "functions/display_exceptions_functions.php";

	require_once "exceptions/FileNotFoundException.php";
	require_once "exceptions/VariableDeclarationNotFoundException.php";
	require_once "exceptions/UnunderstoodVariableDeclarationException.php";
	require_once "exceptions/AbsolutePathReconstructionException.php";
	require_once "exceptions/DependencyNotFoundException.php";
	require_once "exceptions/WrongPathException.php";
	
	


	//Get user settings
	require "parse_settings.php";




	/*******************************************************************************
	********************************************************************************
	**************************** REPOSITORY * SCANNING *****************************
	********************************************************************************
	*******************************************************************************/
	
	//Get array of every file in repo
	$timestamp_directory = microtime(true);
	try {
		$files = getDirContent($repository, $subDirectoriesToIgnore);
		$files = keepSpecificTypesOnly($files, $extensions, $noExtensionFiles);
	}
	catch (RepositoryScanException $e) {
		echo $e->getMessage();
		echo "<br>";
		echo "Can't scan repository. Program end.<br>";
		exit();
	}
	
	$repoName = getRepoName($repository);
	$timestamp_directory = microtime(true) - $timestamp_directory;
	




	
	
	/************************* DATABASE * INITIALISATION **************************/
	$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
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
		try {
			$node = new Node($file, $repoName);
		}
		catch (WrongPathException $e) {
			printQueriesGenerationExceptionMessage($e, $node->getPath());
			continue;
		}

		try {
			try {
				$node->analyseFile();
			}
			catch (VariableDeclarationNotFoundException $e) {
				printAnalysisExceptionMessage($e, $node->getPath());
			}
			catch (UnunderstoodVariableDeclarationException $e) {
				printAnalysisExceptionMessage($e, $node->getPath());
			}
			catch (AbsolutePathReconstructionException $e) {
				printAnalysisExceptionMessage($e, $node->getPath());
			}
			catch (DependencyNotFoundException $e) {
				printAnalysisExceptionMessage($e, $node->getPath());
			}
			catch (WrongPathException $e) {
				printAnalysisExceptionMessage($e, $node->getPath());
			}
			
			//Send node in database
			$uploadQuery = $node->generateUploadQuery();
			if ($uploadQuery) {
				runQuery($client, $uploadQuery);
			}
			
			//Save the object
			array_push($nodes, $node);
		}
		catch (FileNotFoundException $e) {
			printAnalysisExceptionMessage($e, $node->getPath());
		}
		

	}
	echo "<br>Done.<br><br><br><br><br>";
	$timestamp_analyse = microtime(true) - $timestamp_analyse;







	/*******************************************************************************
	********************************************************************************
	****************** STORE * DEPENDENCIES * IN * DATABASE ************************
	********************************************************************************
	*******************************************************************************/
	/**
		STEP 2 : Read informations stored in every node, send relations in database.
	*/
	echo "<h2>STEP 2 UPLOAD DEPENDENCIES</h2>";
	$timestamp_dependencies = microtime(true);
	foreach ($nodes as $node) {
		try {
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
		catch (WrongPathException $e) {
			printQueriesGenerationExceptionMessage($e, $node->getPath());
		}
	}
	echo "<br>Done.<br><br><br><br><br>";
	$timestamp_dependencies = microtime(true) - $timestamp_dependencies;





	/*******************************************************************************
	********************************************************************************
	*************************** DISPLAY * PERFORMANCES *****************************
	********************************************************************************
	*******************************************************************************/
	$timestamp_full = microtime(true) - $timestamp_full;

	echo "<h2>PERFORMANCES</h2>";
	echo "Time to load repository : ".number_format($timestamp_directory, 4)."s<br>";
	echo "Time to analyse repository : " 
		.number_format($timestamp_analyse, 4)."s<br>";
	echo "Time to upload dependencies : "
		.number_format($timestamp_dependencies, 4)."s<br>";
	echo "Script full running time : ".number_format($timestamp_full, 4)."s<br>";
	echo "<br><br>";

?>