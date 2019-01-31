
<?php



	chdir(__DIR__."/../data/projects");

	$dirs = scandir('.');
	foreach ($dirs as $item) {

		if (!is_dir($item)) continue;


		$project = $item;
		$repository = realpath(__DIR__."/../data/projects/$project");
		$repoName = getRepoName($repository);
		$iterationName = 'initialisation';
		$iterationBegin = Date::buildDateFromTimestamp(0);
		$iterationEnd 	= Date::buildDateFromAmericanFormat(date('Y-m-d'), date('H:i'));
		$extensions = array('php');
		$noExtensionFiles = False;
		$featureSyntax = "@feature";
		$subDirectoriesToIgnore = array('.git');
		$filesToIgnore = array();

		$settings = parse_ini_file(__DIR__."/../data/general_settings/database", True, INI_SCANNER_NORMAL);
		$databaseURL = $settings['DATABASE_URL'];
		$databasePort = $settings['DATABASE_PORT'];
		$username = $settings['USERNAME'];
		$password = $settings['PASSWORD'];


		$crawler = new Crawler($repository, $repoName, $iterationName, $iterationBegin, $iterationEnd, $extensions, $noExtensionFiles, $featureSyntax, 
								$subDirectoriesToIgnore, $filesToIgnore, $databaseURL, $databasePort, $username, $password);


		$crawler->crawl();
		echo "\n\n\n\n\n\n";
		
	}

	echo "\n\n\n";
	
	echo "Program successfully completed.\n";




?>