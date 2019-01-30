<?php
	
	error_reporting(E_ALL);
	$timestamp_full = microtime(TRUE);


	require __DIR__.'/../vendor/autoload.php';

	use GuzzleHttp\Client;

	$client = new Client(['base_uri' => 'https://api.github.com']);

	//header : Accept: application/vnd.github.v3+json

	//Token : 369feaa6df0d0df8d04ee1d9a2dfbee6d0b191dc

	/*
	$res = $client->request('GET', '/users/WustmannMatthias/repos?per_page=100', [
							//'auth' => ['WustmannMatthias', '369feaa6df0d0df8d04ee1d9a2dfbee6d0b191dc'],
							'Accept' => 'application/vnd.github.v3+json'
							]);


	if (!$res->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res->getStatusCode();
		exit();
	}


	$repos = json_decode($res->getBody());

	$sshUrls = array();
	foreach ($repos as $repo) {
		if ($repo->name != 'PHP-Modeller') {
			$sshUrls[$repo->name] = $repo->ssh_url;
		}
	}
	*/
	
	$res1 = $client->request('GET', '/orgs/flash-global/repos?per_page=100', [
							'auth' => ['WustmannMatthias', $token],
							'Accept' => 'application/vnd.github.v3+json'
							]);
	$res2 = $client->request('GET', '/orgs/flash-global/repos?per_page=100&page=2', [
							'auth' => ['WustmannMatthias', $token],
							'Accept' => 'application/vnd.github.v3+json'
							]);
	$res3 = $client->request('GET', '/orgs/flash-global/repos?per_page=100&page=3', [
							'auth' => ['WustmannMatthias', $token],
							'Accept' => 'application/vnd.github.v3+json'
							]);

	if (!$res1->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res1->getStatusCode();
		exit();
	}
	if (!$res2->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res2->getStatusCode();
		exit();
	}
	if (!$res3->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res3->getStatusCode();
		exit();
	}


	$data1 = json_decode($res1->getBody());
	$data2 = json_decode($res2->getBody());
	$data3 = json_decode($res3->getBody());

	$sshUrls = array();

	foreach ($data1 as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}
	foreach ($data2 as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}
	foreach ($data3 as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}




	/**
	 *	Once we got the ssh urls, let's clone every repo and install dependencies
	 */

	chdir(__DIR__."/../data/projects");
	foreach ($sshUrls as $name => $url) {
		passthru('git clone '.$url, $output);
		
		if (in_array('composer.json', scandir($name))) {
			chdir($name);
			passthru('composer install');
			chdir('..');
		}
		
	}

	echo "\n\n\n";
	


	/**
	 *	Then, call the crawler for every repo to initialise the full database
	 */

	require_once __DIR__.'/../objects/Crawler.php';
	require_once __DIR__.'/../objects/Date.php';
	require_once __DIR__.'/../functions/common_functions.php';
	

	foreach ($sshUrls as $name => $url) {
		
		$project = $name;
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

	




?>