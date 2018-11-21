<?php

	/**
		Scans (recursively) a directory and returns all complete filenames within it in an array
		@param dir is the path to a directory
		@param tab is the array in witch you want to put all filenames of the files of the directory
		@return is an array
	*/
	function scanDirectory($dir, $tab) {
		if (!is_dir($dir)) { //is it a directory ?
			throw new Exception("$dir is not a directory");
		}
		if (!($dh = opendir($dir))) { //do we have access rights ?
			throw new Exception("Access denied to $dir");	
		}
		
		$dirsInDir = array(); //To save directories in current directory
		while (($item = readdir($dh)) !== false) {//Go through everything in directory
			if ($item == "." || $item == "..") { //Avoid going backward in directories
				continue; 
			}

			$fullPathToItem = $dir."/".$item;

			//First all files have to be added to $tab. Then, we can enter into directories
			if (is_dir($fullPathToItem)) {
				array_push($dirsInDir, $fullPathToItem);
			}
			else {
				array_push($tab, $fullPathToItem); //Save all filenames in $tab
			}
		}
		closedir($dh);

		//Now we enter into each sub directory
		foreach ($dirsInDir as $subDir) {
			$tab = array_merge($tab, scanDirectory($subDir, $tab));
		}

		//Finally return the complete $tab with all filenames
		return array_unique($tab);
	}

	function getDirContents($dir, &$results = array()){
		$files = scandir($dir);

		foreach($files as $key => $value){
			$path = realpath($dir.DIRECTORY_SEPARATOR.$value);
			if(!is_dir($path)) {
				$results[] = $path;
			} else if($value != "." && $value != "..") {
				getDirContents($path, $results);
				//$results[] = $path;
			}
		}

		return $results;
	}



	/**
		Take a filenames array and return a new array containing only file with one of the given extensions. 
		@param filenames and extensions are arrays
		@return is an array
	*/
	function keepSpecificTypesOnly($filenames, $extensions) {
		$output = array();
		 foreach ($filenames as $filename) {
		 	foreach ($extensions as $extension) {
		 		if (endswith($filename, $extension)) {
					array_push($output, $filename);
				} 
		 	}
		}
		return $output;
	}


	/**
		Take the URL of a github repository or a path of the directory and gives back 
		the name of the repo
		@param $path is a String
		@return is a String
	*/
	function getRepoName($path) {
		$endPath = @end(explode("/", $path));
		return str_replace(".git", "", $endPath);
	}


?>