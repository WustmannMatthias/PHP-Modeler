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
	




	
	
	/*******************************************************************************
	********************************************************************************
	*************************** DATABASE * INITIALISATION **************************
	********************************************************************************
	*******************************************************************************/
	/**
		Project layer : 
		We want to keep relation between :File nodes and :Iteration nodes, to have
		a history of modification.
		-> Just remove everything that is not a File or an Iteration, and remove All
			relations bewteen files : thoses will be reanalysed and remodeled.
			Then Node class will take care of making updates and reupload dependencies,
			features, namespaces.

		Database Layer : 
		We want to do the same, but only with the project we are analysing : every other
		repositories represented in the database have to be left alone
	*/
	$timestamp_database = microtime(TRUE);

	$fullURL = "bolt://".$username.":".$password."@".$databaseURL.":".$databasePort;
	$client = ClientBuilder::create()
	    ->addConnection('bolt', $fullURL)
	    ->build();
	/*
	runQuery($client, "MATCH (n)-[r:IS_INCLUDED_IN|:IS_REQUIRED_IN
								|:IMPACTS|:DECLARES|:IS_USED_BY]
								->(n2) DELETE r");
	runQuery($client, "MATCH (n:Namespace), (f:Feature) DELETE n, f");
	*/
	
	//echo $query."\n";
	
	runQuery($client, "MATCH (repoFiles:File),
						(repoFiles)<-[repoUses:IS_USED_BY]-(:Namespace)
						WHERE repoFiles.path STARTS WITH '$repoName'
						DELETE repoUses");

	runQuery($client, "MATCH (repoFiles:File),
						(repoNS:Namespace)<-[repoNSDeclarations:DECLARES]-(repoFiles)
						WHERE repoFiles.path STARTS WITH '$repoName'
						DELETE repoNSDeclarations, repoNS");

	runQuery($client, "MATCH (repoFiles:File),
						(repoFeatures:Feature)<-[repoImpacts:IMPACTS]-(repoFiles)
						WHERE repoFiles.path STARTS WITH '$repoName'
						DELETE repoImpacts, repoFeatures");

	runQuery($client, "MATCH (repoFiles:File),
						(:File)-[repoInclusions:IS_REQUIRED_IN|:IS_INCLUDED_IN]
						->(repoFiles)
						WHERE repoFiles.path STARTS WITH '$repoName'
						DELETE repoInclusions");
	

	// Already get array from files with their modification date in last modelisation 
	// to win time later
	$filesInDB = array();
	$result = runQuery($client, "MATCH (n:File) RETURN n.path as path, 
						n.last_modified as last_modified");
	foreach ($result->records() as $record) {
		$path = $record->value('path');
		$last_modified = $record->value('last_modified');
		$filesInDB[$path] = Date::buildDateFromTimestamp($last_modified);
	}

	include_once "objects/Node.php";
	Node::setOldFileList($filesInDB);
	///displayArray(Node::getOldFileList());
	$timestamp_database = microtime(TRUE) - $timestamp_database;







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
	echo "############### STEP 1 ANALYSE ###############\n";
	echo "Files to analyse : ".sizeof($files);
	echo "\n\n";

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
	echo "\n\n\nDone.\n\n";
	$timestamp_analyse = microtime(TRUE) - $timestamp_analyse;







	/*******************************************************************************
	********************************************************************************
	****************** STORE * DEPENDENCIES * IN * DATABASE ************************
	********************************************************************************
	*******************************************************************************/
	/**
		STEP 2 : Read informations stored in every node, send relations in database.
	*/
	echo "############### STEP 2 UPLOAD DEPENDENCIES ###############\n\n";
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
	echo "\n\n\nDone.\n\n";
	$timestamp_dependencies = microtime(TRUE) - $timestamp_dependencies;









	/*******************************************************************************
	********************************************************************************
	***************** ADD * ITERATION * NODE * IN * DATABASE ***********************
	********************************************************************************
	*******************************************************************************/
	/**
		STEP 3 : Add iterations in database
	*/
	echo "############### STEP 3 ADD ITERATION ###############\n\n";
	$timestamp_iteration = microtime(TRUE);
	
	// Just prepare variables
	$begin 	= $iterationBegin->getTimestamp();
	$end 	= $iterationEnd->getTimestamp();
	$repoName = $nodes[0]->getRepoName(); // All nodes belongs to the same repo
	
	$atLeastOne = FALSE;
	foreach ($nodes as $node) {
		if ($node->getLastModified()->isBetween($iterationBegin, $iterationEnd)) {
			$atLeastOne = TRUE;
			$path = Node::getPathFromRepo($node->getPath(), $node->getRepoName());

			$query = "MATCH  (f:File {path: '".$path."'}) 
					  MERGE  (i:Iteration {name: '$iterationName', 
					  					   project: '$repoName',
					  					   begin: $begin,
					  					   end: $end }) 
					  MERGE (f)-[:BELONGS_TO]->(i) ";
			//echo $query."\n\n";
			runQuery($client, $query);
		}
	}

	if (!$atLeastOne) {
		runQuery($client, "CREATE (i:Iteration {name: '$iterationName', 
												project: '$repoName', 
												begin: $begin, 
												end: $end })");
	}

	echo "\n\n\nDone.\n\n";
	$timestamp_iteration = microtime(TRUE) - $timestamp_iteration;







	/*******************************************************************************
	********************************************************************************
	*************************** DISPLAY * PERFORMANCES *****************************
	********************************************************************************
	*******************************************************************************/
	$timestamp_full = microtime(TRUE) - $timestamp_full;

	echo "############### PERFORMANCES ###############\n\n";
	echo "Time to load repository : "
		.number_format($timestamp_directory, 4)."s\n";
	echo "Time to prepare database : "
		.number_format($timestamp_database, 4)."s\n";
	echo "Time to analyse repository : " 
		.number_format($timestamp_analyse, 4)."s\n";
	echo "Time to upload dependencies : "
		.number_format($timestamp_dependencies, 4)."s\n";
	echo "Time to add iteration : "
		.number_format($timestamp_iteration, 4)."s\n";
	echo "Script full running time : ".number_format($timestamp_full, 4)."s\n";
	
	echo "\n\n\nExit.\n\n";

?>