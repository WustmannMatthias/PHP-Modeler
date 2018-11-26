<?php
	require_once 'functions/common_functions.php';
	require_once 'constants.inc';


	$settings = parse_ini_file("settings", true, INI_SCANNER_NORMAL);

	/**
		Just separate multiple parameters and returns an array with all trimed parameters
		@param parameters is the String with parameters in it
		@param separator is the caracter used to separate parameters
		@return is an array
	*/
	function parseParameters($parameters) {
		$output = array();
		$parametersArray = explode(PARAMETERS_SEPARATOR, $parameters);
		foreach ($parametersArray as $parameter) {
			array_push($output, trim($parameter));
		}
		return $output;
	}

	$repository = $settings['REPOSITORY'];

	$databaseURL = $settings['DATABASE_URL'];
	$databasePort = $settings['DATABASE_PORT'];
	$username = $settings['USERNAME'];
	$password = $settings['PASSWORD'];
	
	$subDirectoriesToIgnore = parseParameters($settings['SUB_DIRECTORIES']);
	array_push($subDirectoriesToIgnore, '.', '..');
	$subDirectoriesToIgnore = array_unique($subDirectoriesToIgnore);
	
	$extensions = parseParameters($settings['EXTENSIONS']);
	
	$noExtensionFiles = $settings['NO_EXTENSION_FILES'];


	//displayArray($settings);

?>