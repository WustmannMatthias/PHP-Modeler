<?php
	$directory = "application_modeling";
	
	exec('whoami', $output);
	$user = $output[0];
	$home = "/home/$user";

	chdir($home);

	if (!is_dir($directory)) {
		exec('mkdir application_modeling');	
	}

	chdir($directory);

	include "parse_settings.php";


	if (is_dir($repoName)) {
		chdir($repoName);
		exec("git pull origin master");
	}
	else {
		exec("git clone $repository")
	}

	echo "Done\n\n";

?>