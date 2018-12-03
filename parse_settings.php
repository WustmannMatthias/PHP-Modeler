<?php
	require_once 'functions/common_functions.php';


	$settings = parse_ini_file("settings", true, INI_SCANNER_NORMAL);

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

	$repository = $settings['REPOSITORY'];
	if (endswith($repository, '/')) {
		$repository = substr($repository, 0, strlen($repository) - 1);
	}
	$repoName = getRepoName($repository);


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


	
	

	//displayArray($settings);

?>