<?php
	/*
		§§ Run programm
		§§ Modelise a repository
	*/

	/*******************************************************************************
	********************************************************************************
	******************************** INITIALISATION ********************************
	********************************************************************************
	*******************************************************************************/

	error_reporting(E_ALL);
	$timestamp_full = microtime(TRUE);

	require_once "vendor/autoload.php";

	require_once "autoloader.php";
	
	use GraphAware\Neo4j\Client\ClientBuilder;

	require_once "functions/common_functions.php";
	require_once "functions/repo_scan_functions.php";
	require_once "functions/database_functions.php";
	require_once "functions/display_exceptions_functions.php";
	


	//Get user settings
	require_once "parse_settings.php";



	/*******************************************************************************
	********************************************************************************
	**************************** REPOSITORY * SCANNING *****************************
	********************************************************************************
	*******************************************************************************/
	
	//Get array of every file in repo
	$timestamp_directory = microtime(TRUE);
	try {
		$files = getDirContent($repository, $subDirectoriesToIgnore, $filesToIgnore);
		$files = keepSpecificTypesOnly($files, $extensions, $noExtensionFiles);
	}
	catch (RepositoryScanException $e) {
		echo $e->getMessage();
		echo "\n";
		echo "Can't scan repository. Program end.\n";
		exit();
	}
	
	$repoName = getRepoName($repository);
	$timestamp_directory = microtime(TRUE) - $timestamp_directory;
	




	
	
	/************************* DATABASE * INITIALISATION **************************/
	$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
	    ->build();
	runQuery($client, "MATCH (n)-[r]->(n2) DELETE r");
	runQuery($client, "MATCH (n:Namespace), (f:Feature) DELETE n, f");
	

	# Already get file list to win time later
	$filesInDB = array();
	$result = runQuery($client, "MATCH (n:File) RETURN n.path as path, 
						n.last_modified as last_modified");
	foreach ($result->records() as $record) {
		$path = $record->value('path');
		$last_modified = $record->value('last_modified');
		$filesInDB[$path] = $last_modified;
	}

	include_once "objects/Node.php";
	Node::setOldFileList($filesInDB);
	///displayArray(Node::getOldFileList());


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
	echo "<h2>STEP 1 ANALYSE</h2>\n";
	echo "Files to analyse : ".sizeof($files);
	echo "\n\n\n\n";

	$timestamp_analyse = microtime(TRUE);
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
			catch (WrongDependencyTypeException $e) {
				printAnalysisExceptionMessage($e, $node->getPath());
			}
			catch (UnunderstoodNamespaceDeclarationException $e) {
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
	echo "\nDone.\n\n\n\n\n";
	$timestamp_analyse = microtime(TRUE) - $timestamp_analyse;







	/*******************************************************************************
	********************************************************************************
	****************** STORE * DEPENDENCIES * IN * DATABASE ************************
	********************************************************************************
	*******************************************************************************/
	/**
		STEP 2 : Read informations stored in every node, send relations in database.
	*/
	echo "<h2>STEP 2 UPLOAD DEPENDENCIES</h2>\n";
	$timestamp_dependencies = microtime(TRUE);
	foreach ($nodes as $node) {
		try {
			//Send include/require relations in database
			$fileInclusionsQuery = $node->generateFileInclusionsRelationQuery();
			if ($fileInclusionsQuery) {
				runQuery($client, $fileInclusionsQuery);
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
	echo "\nDone.\n\n\n\n\n";
	$timestamp_dependencies = microtime(TRUE) - $timestamp_dependencies;





	/*******************************************************************************
	********************************************************************************
	*************************** DISPLAY * PERFORMANCES *****************************
	********************************************************************************
	*******************************************************************************/
	$timestamp_full = microtime(TRUE) - $timestamp_full;

	echo "<h2>PERFORMANCES</h2>\n";
	echo "Time to load repository : ".number_format($timestamp_directory, 4)."s\n";
	echo "Time to analyse repository : " 
		.number_format($timestamp_analyse, 4)."s\n";
	echo "Time to upload dependencies : "
		.number_format($timestamp_dependencies, 4)."s\n";
	echo "Script full running time : ".number_format($timestamp_full, 4)."s\n";
	echo "\n\n";

?>