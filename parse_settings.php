<?php
	require_once 'functions/common_functions.php';

	/**
		Just separate multiple parameters and returns an array with all trimed parameters
		@param parameters is the String with parameters in it
		@param separator is the caracter used to separate parameters
		@return is an array
	*/
	function parseParameters($parameters) {
		$output = array();
		$parametersArray = explode(',', $parameters);
		foreach ($parametersArray as $parameter) {
			array_push($output, trim($parameter));
		}
		return $output;
	}


	/******************* PARSE SETTINGS FILE *********************/


	$settings = parse_ini_file("settings", true, INI_SCANNER_NORMAL);


	$databaseURL = $settings['DATABASE_URL'];
	$databasePort = $settings['DATABASE_PORT'];
	$username = $settings['USERNAME'];
	$password = $settings['PASSWORD'];
	
	$extensions = parseParameters($settings['EXTENSIONS']);
	
	$noExtensionFiles = $settings['NO_EXTENSION_FILES'];

	$featureSyntax = $settings['FEATURE_SYNTAX'];

	$subDirectoriesToIgnore = parseParameters($settings['SUB_DIRECTORIES']);
	array_push($subDirectoriesToIgnore, '.', '..');
	$subDirectoriesToIgnore = array_unique($subDirectoriesToIgnore);

	$filesToIgnore = parseParameters($settings['FILES']);




	/******************* PARSE ITERATION FILE *********************/

	try {
		$iterationSettings = file_get_contents('iteration');
	}
	catch (Exception $e) {
		echo "File iteration missing. Can't proceed.\n";
		echo $e->getMessage();
		exit();
	}

	$iterationSettings = explode("\n", $iterationSettings);

	$repository = $iterationSettings[0];
	$repoName = getRepoName($repository);
	
	$dateBegin = $iterationSettings[1];
	$dateEnd = $iterationSettings[2];
	

	//displayArray($settings);

?>