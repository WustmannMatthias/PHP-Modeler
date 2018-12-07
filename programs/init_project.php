<?php
	error_reporting(E_ALL);
	if (isset($_GET['project'])) {
		$project = $_GET['project'];



		/**
			SETTINGS
		*/
		if (isset($_POST['changeSettings'])) {

			$settingsFile = "/var/www/html/application_modeling_2.0/data/projects_settings/$project";
			
			$settings = "";
			if (isset($_POST['extensions'])) $settings.="EXTENSIONS=".$_POST['extensions']."\n";
			if (isset($_POST['withoutExtension'])) $settings.="NO_EXTENSION_FILES=".$_POST['withoutExtension']."\n";
			if (isset($_POST['feature'])) $settings.="FEATURE_SYNTAX=".$_POST['feature']."\n";
			if (isset($_POST['subDirectories'])) $settings.="SUB_DIRECTORIES=".$_POST['subDirectories']."\n";
			if (isset($_POST['filesToIgnore'])) $settings.="FILES=".$_POST['filesToIgnore']."\n"; else $settings.="FILES=";

			file_put_contents($settingsFile, $settings);





			/**
				CREATE FIRST ITERATION CONFIG FILE
			*/
			$iterationFile = "/var/www/html/application_modeling_2.0/data/general_settings/iteration";

			$iterationSettings = "REPOSITORY=$project\n";
			$iterationSettings.= "ITERATION_NAME=initialisation\n";
			$iterationSettings.= "DATE_BEGIN=1970-01-01\n";
			$iterationSettings.= "TIME_BEGIN=00:00\n";
			$iterationSettings.= "DATE_END=".date('Y-m-d')."\n";
			$iterationSettings.= "TIME_END=".date('H:i')."\n";

			file_put_contents($iterationFile, $iterationSettings);






			/**
				LAUNCH ENGINE
			*/
			header("Location: ../crawler.php");

			echo "<br><br><br>";
			echo "<a href='../index.php'>Back to homepage</a>";

		}
		else {
			echo '$_POST["changeSettings"] is not set.';
			exit();
		}
	}
	else {
		echo '$_GET["project"] is not set.';
		exit();
	}

?>