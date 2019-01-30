<?php
	
	error_reporting(E_ALL);
	$timestamp_full = microtime(TRUE);


	require __DIR__.'/../vendor/autoload.php';

	use GuzzleHttp\Client;

	$client = new Client(['base_uri' => 'https://api.github.com']);

	//header : Accept: application/vnd.github.v3+json

	//Token : 369feaa6df0d0df8d04ee1d9a2dfbee6d0b191dc

	
	$res = $client->request('GET', '/users/WustmannMatthias/repos?per_page=100', [
							//'auth' => ['WustmannMatthias', '369feaa6df0d0df8d04ee1d9a2dfbee6d0b191dc'],
							'Accept' => 'application/vnd.github.v3+json'
							]);

	
	/*
	$res = $client->request('GET', '/user/repos', [
							'auth' => ['WustmannMatthias', '369feaa6df0d0df8d04ee1d9a2dfbee6d0b191dc'],
							'Accept' => 'application/vnd.github.v3+json'
							]);
	*/
	

	/*
	$res = $client->request('GET', '/orgs/flash-global/repos?per_page=100', [
							'auth' => ['WustmannMatthias', '369feaa6df0d0df8d04ee1d9a2dfbee6d0b191dc'],
							'Accept' => 'application/vnd.github.v3+json'
							]);
	*/

	if (!$res->getStatusCode() == 200)  {
		echo "Error : Http request got status code ".$res->getStatusCode();
		exit();
	}


	$repos = json_decode($res->getBody());

	$sshUrls = array();
	foreach ($repos as $repo) {
		$sshUrls[$repo->name] = $repo->ssh_url;
	}







	chdir(__DIR__."/../data/projects");
	foreach ($sshUrls as $name => $url) {
		passthru('git clone '.$url, $output);
		
		if (in_array('composer.json', scandir($name))) {
			chdir($name);
			passthru('composer install');
			chdir('..');
		}
		
	}
	


?>