<?php
	/**
		§§ Parse Settings
	*/
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

	$iterationSettings = parse_ini_file("iteration", true, INI_SCANNER_NORMAL);

	$repository = $iterationSettings['REPOSITORY'];
	$repoName = getRepoName($repository);
	$iterationName 	= $iterationSettings['ITERATION_NAME'];
	
	$iterationBegin = Date::buildDateFromCalendar (	
								$iterationSettings['ITERATION_BEGIN_YEAR'],
								$iterationSettings['ITERATION_BEGIN_MONTH'],
								$iterationSettings['ITERATION_BEGIN_DAY'],
								$iterationSettings['ITERATION_BEGIN_HOUR'],
								$iterationSettings['ITERATION_BEGIN_MINUTE']
					);
	
	$iterationEnd 	= Date::buildDateFromCalendar (	 
								$iterationSettings['ITERATION_END_YEAR'],
							  	$iterationSettings['ITERATION_END_MONTH'],
							  	$iterationSettings['ITERATION_END_DAY'],
							  	$iterationSettings['ITERATION_END_HOUR'],
							  	$iterationSettings['ITERATION_END_MINUTE']
					);


	//displayArray($settings);

?>