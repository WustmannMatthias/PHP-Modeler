<?php
	
	/*
		§§ File scan
		§§ Upload queries generation
	*/

	require_once "functions/common_functions.php";

	define("FEATURE_DEFINITION", "§§");




	Class Node {

		/**
			This class represents a Node in the modelisation. There should be one for each file of a repo.
			Following attributes stores informations about the file.
		*/

		private $_path;
		private $_name;
		private $_size;
		private $_extension;
		private $_lastModified;
		private $_repoName;
		
		private $_features;

		private $_includes;
		private $_requires;
		private $_namespaces;
		private $_globalVariables;
		private $_uses;


		/**
			Get values of all attributes of the instance juste from the accesspath
			@param path is a String -> absolute path to file
			@param repoName is a String -> name of the repo
		*/
		public function __construct($path, $repoName) {
			$this->_path			= $path;

			$this->_repoName		= $repoName;

			$this->_name 			= $this->pickUpName($path);
			$this->_extension		= $this->pickUpExtension($path);
			$this->_size 			= $this->pickUpSize($path);
			$this->_lastModified 	= $this->pickUpLastModified($path);
			
			$this->_features		= array();
			
			$this->_includes 		= array();
			$this->_requires 		= array();
			$this->_namespaces 		= array();
			$this->_uses 			= array();
		}


		/**
			Functions called by constructor to get infos about a file
			@param path is a String and represents absolute path to file.
		*/
		private function pickUpSize($path) {
			return filesize($path);
		}
		private function pickUpName($path) {
			return @end(explode('/', $path));
		}
		private function pickUpLastModified($path) {
			return date('d.m.Y H:i:s', filemtime($path));
		}
		private function pickUpExtension($path) {
			return @end(explode('.', $path));
		}






		/*******************************************************************************
		********************************************************************************
		**************************** FILE * STATIC * ANALYSIS **************************
		********************************************************************************
		*******************************************************************************/


		/**
			Main method of the class
			This method run one time through every line of the file, analyse it, and store
			following registred informations in attributes of the instance : 
			- file included/required in this one
			- outside classes used
			- declared namespaces
			- declared features
		*/
		public function analyseFile() {
			$inComment = false;
			$lineCount = 0;

			try {
				$fileHandler = fopen($this->_path, 'r');
				while (!feof($fileHandler)) {
					$line = trim(fgets($fileHandler));
					$lineCount ++;

					$this->analyseFeatures($line);
					
					// Comments handling
					if (startsWith($line, "//")) continue;
					if (startsWith($line, "/*")) $inComment = true;
					if ($inComment) {
						if (strpos($line, "*/") === false) continue;
						else $inComment = false;
					}

					$this->analyseIncludes($line, $lineCount);
					$this->analyseRequires($line, $lineCount);
					$this->analyseNameSpaces($line);
					$this->analyseUses($line);
				}
			}
			catch (Exception $e) {
				echo $e->getMessage()."<br>";
			}
		}

		/**
			Features are declared in code by developpers by a specific syntax in 
			header of the file.
			This method analyse a line to find this syntax, and store the feature 
			@param line is a String
		*/
		private function analyseFeatures($line) {
			if (startsWith($line, FEATURE_DEFINITION)) {
				$feature = trim(str_replace(FEATURE_DEFINITION, '', $line));
				array_push($this->_features, $feature);
			}
		}

		/**
			Matches include statements and extract argument.
			If the argument is composed of variables, they will be replaced by their
			values. However, if the variable is defined in an other file, the value won't be
			found.
			@param line is the line to analyse (String)
			@param lineCount is the number of the line (int)
		*/
		private function analyseIncludes($line, $lineCount) {
			$regex = "/include(_once)?\s+[-_ A-za-z0-9\$\.\"']+/";
			if (preg_match($regex, $line)) { 
				if ($this->isVariableInLine($line)) {
					$line = $this->replaceVariable($line, $lineCount);
				}
				$line = $this->removeUnnecessary($line);
				$line = $this->removeDoubleSlash($line);
				$path = $this->fillPath($line);

				array_push($this->_includes, $path);
			}
		}

		/**
			Like analyseIncludes($line, $lineCount), but for require statements
		*/
		private function analyseRequires($line, $lineCount) {
			$regex = "/require(_once)?\s+[-_ A-za-z0-9\$\.\"']+/";
			if (preg_match($regex, $line)) { 
				if ($this->isVariableInLine($line)) {
					$line = $this->replaceVariable($line, $lineCount);
				}
				$line = $this->removeUnnecessary($line);
				$line = $this->removeDoubleSlash($line);
				$path = $this->fillPath($line);

				array_push($this->_requires, $path);
			}
		}

		/**
			Matches namespace statements and extract argument.
			@param line is the line to analyse (String)
		*/
		private function analyseNameSpaces($line) {
			$regex = "/^namespace\s+[-_ A-za-z0-9\\\]+/";
			if (preg_match($regex, $line)) {
				$namespace = $this->extractNamespace($line);
				array_push($this->_namespaces, $namespace);
			}
		}

		/**
			Matches use statements and extract argument.
			@param line is the line to analyse (String)
		*/
		private function analyseUses($line) {
			$regex = "/^use\s+[-_ A-za-z0-9\\\]+/";
			if (preg_match($regex, $line)) {
				$use = $this->extractUses($line);
				array_push($this->_uses, $use);
			}
		}



		/**
			Check if a variable is used in a code line.
			@param line (string) is the line to analyse
			@return is a boolean
		*/
		private function isVariableInLine($line) {
			if (strpos($line, '$') === false) { //=== because '$' can be at index 0
				return false;
			}
			return true;
		}

		/**
			Check if a the magic constant __DIR__ is used in a code line.
			@param line (string) is the line to analyse
			@return is a boolean
		*/
		private function isMagicConstantInLine($line) {
			if (strpos($line, '__DIR__') === false) { //=== because '$' can be at index 0
				return false;
			}
			return true;
		}
		

		/**
			Take a code line containing a variable, and replace it by her value
			@param line (string) is the line
			@param lineCount (int) is the number of the line
			@return (string) is the new line
		*/
		private function replaceVariable($line, $lineCount) {
			$variableName = $this->identifyVariable($line);
			//echo $variableName." : ";
			$value = $this->findVariableValue($variableName, $lineCount);
			//echo $value." ------- ";
			$line = $this->replaceVariableWithValue($line, $variableName, $value);
			//echo $line."<br>";
			return $line;
		}
		
		/**
			Identify the use of a variable in a code line. This function can be used only if
			it is sure that a there is a variable in the line (see function 
			isVariableInLine(string) to test presence of variable.
			@param line (string) is the line
			@return (string) is the name of the variable (with the $)
		*/
		private function identifyVariable($line) {
			$endVariableChar = array('.', ';', ' ');
			$tab = str_split($line);
			$inVariable = 0;
			$variableName = "";
			foreach ($tab as $index => $character) {
				if ($character === "$") {
					$inVariable = 1;
				}
				if ($inVariable && in_array($character, $endVariableChar)) {
					break;
				}
				if ($inVariable) {
					$variableName.= $character;
				}
			}
			return $variableName;
		}

		/**
			Find where a given variable is declared in file, and return her value
			@param variableName (string) is the name of the variable
			@param maxLine (int) is the line where the variable is used in the include 
				statement (the declaration is necessarily before)
			@return (string) is the value of the variable
		*/
		private function findVariableValue($variableName, $maxLine) {
			//First, go through file and find variable in file
			$lineCount = 0;
			$fileHandler = fopen($this->_path, 'r');
			$line;
			while (!feof($fileHandler)) {
				$line = fgets($fileHandler);
				$lineCount ++;
				if (startsWith(trim($line), $variableName)) {
					fclose($fileHandler);
					break;
				} 
				if ($lineCount >= $maxLine) {
					throw new Exception("Declaration of variable $variableName not found in ".$this->getPathFromRepo().".<br>");
				}	
			}
			//Then, analyse line et get value
			$tab = str_split($line);
			if (in_array('"', $tab)) {
				return explode('"', $line)[1];
			}
			else if (in_array("'", $tab)) {
				return explode("'", $line)[1];
			}
			else {
				throw new Exception("Ununderstood variable declaration in ".$this->_path." , line ".$lineCount.".");
			}
		}

		/**
			Replace a variable with her value in an include or require line, and put 
			double quotes around it.
			@param line (string) is the include line
			@param variableName (string) is the name of the variable (with the $)
			@param value (string) is the value of the variable
			@return (string) is the modified line
		*/
		private function replaceVariableWithValue($line, $variableName, $value) {
			return str_replace($variableName, '"'.trim($value).'"', $line);
		}


		/**
			Replace the magic constant __DIR__ with the corresponding path
			@param line is the line with the magic constant in it (String)
			@return is also a String
		*/
		private function replaceMagicConstant($line) {
			$dirPath = str_replace($this->_name, "", $this->_path);
			$newline = str_replace("__DIR__", '"'.$dirPath.'"', $line);
			return $newline;
		}


		/**
			Help function : removes everything that is not a part of the path to a file
			@param line (string) is the line of the file where a file is included
			@return (string) is the new line
		*/
		private function removeUnnecessary($line) {
			$tab = str_split(trim($line));
			$inSimpleQuotes = false;
			$inDoubleQuotes = false;
			$output = "";
			foreach ($tab as $character) {
				if ($character == "'") {
					$inSimpleQuotes = !$inSimpleQuotes;
				}
				if ($character == '"') {
					$inDoubleQuotes = !$inDoubleQuotes;
				}
				if (($inSimpleQuotes || $inDoubleQuotes) && $character != "'" && $character != '"') {
					$output.= $character;
				}
			}
			return $output;
		}


		/**
			Removes eventual '//' in path
			@param line is a String
			@return is a String
		*/
		private function removeDoubleSlash($line) {
			return str_replace('//', '/', $line);
		}


		/**
			Takes a line with a namespace statement and returns only the argument
			@param line is a String
			@return is a String
		*/
		private function extractNamespace($line) {
			$line = substr($line, 0, strlen($line) - 1);
			return trim(str_replace("namespace ", "", $line));
		}

		/**
			Takes a line with a use statement and returns only the argument
			@param line is a String
			@return is a String
		*/
		private function extractUses($line) {
			$line = substr($line, 0, strlen($line) - 1);
			return trim(str_replace("use ", "", $line));
		}


		/**
			Takes a relative path and returns an absolute path
			@param dependenciesTab is an array of relative path
			@param output is an array of absolute path
		*/
		private function fillPath($path) {
			$currentFilePath = str_replace($this->_name, "", $this->_path);
			$dependencyPath = $path;

			$dependencyStartPath = $currentFilePath;
			$dependencyEndPath = $dependencyPath;
			
			while (startsWith($dependencyPath, "../")) {
				$dependencyEndPath = substr($dependencyPath, 3);
				$dependencyPath = $dependencyEndPath;

				$dependencyStartPath = substr($currentFilePath, 0, -1);
				$dependencyStartPath = implode('/', array_trim_end(explode('/', $dependencyStartPath))).'/';
			}
			return $dependencyStartPath.$dependencyEndPath;
		}


		/**
			Replace each \ with \\ in the namespaces. Returns the new array
			@return is an array
		*/
		private function prepareNamespaces() {
			$output = array();
			foreach ($this->_namespaces as $namespace) {
				array_push($output, str_replace("\\", "\\\\", $namespace));
			}
			return $output;
		}




		/*******************************************************************************
		********************************************************************************
		****************************** QUERY * GENERATION ******************************
		********************************************************************************
		*******************************************************************************/


		/**
			Generates à Cypher Query that creates a Node for this instance in the 
			neo4j Database
			@return is a String
		*/
		public function generateUploadQuery() {
			//Create Node
			$query = "CREATE (n:File {name: '".$this->_name
								."', path: '".$this->getPathFromRepo($this->_path, 
									$this->_repoName)
								."', size: '".$this->_size
								."', lastModified: '".$this->_lastModified
								."', extension: '".$this->_extension
								."'}) ";

			//Foreach of his features, create Node and relationship if not already exists
			$counter = 0;								
			foreach ($this->_features as $feature) {
				$counter ++;
				$query.= "MERGE (f".$counter.":Feature {name: '$feature'}) ";
				$query.= "CREATE (n)-[:IMPACT]->(f".$counter.") ";
			}

			//Foreach of his namespaces, create Node and relationship if not already exists
			$counter = 0;
			foreach ($this->prepareNamespaces() as $namespace) {
				$counter ++;
				$query.= "MERGE (ns".$counter.":Namespace {name: '$namespace'}) ";
				$query.= "CREATE (n)-[:DEFINES]->(ns".$counter.") ";	
			}

			echo $query."<br>";
			return $query;
		}



		/**
			Generates à Cypher Query that identifies this instance in the neo4j Database
			@return is a String
		*/
		public function generateMatchQuery() {
			return "MATCH (n:File {path: '".$this->getPathFromRepo($this->_path, 
					$this->_repoName)."'}) RETURN n";
		}
		


		/**
			Generates a Cypher Query that creates every relation between this nodes and the
			nodes included in it.
			@return is a mixed value : 
				- if there aren't any nodes included, returns false
				- if there are, return is a String : the Cypher query
		*/
		public function generateIncludeRelationQuery() {
			if (sizeof($this->_includes) == 0) {
				return false;
			}

			$includeRelation = "IS_INCLUDED_BY";

			$path 		= $this->getPathFromRepo($this->_path, $this->_repoName);
			$queryBegin = "MATCH (ni:File {path: '".$path."'}) " ;
			$queryEnd	= "";

			$iCounter = 0;
			foreach ($this->_includes as $include) {
				$includePath = $this->getPathFromRepo($include, $this->_repoName);
				$iCounter ++;
				$queryBegin .= "MATCH (ni".$iCounter.":File {path: '$includePath'}) ";
				$queryEnd 	.= "CREATE (ni".$iCounter.")-[ri".$iCounter.":".$includeRelation
						."]->(ni) ";
			}

			$query = $queryBegin.$queryEnd;
			return $query;
		}


		/**
			Generates a Cypher Query that creates every relation between this nodes and the
			nodes required in it.
			@return is a mixed value : 
				- if there aren't any nodes required, returns false
				- if there are, return is a String : the Cypher query
		*/
		public function generateRequireRelationQuery() {
			if (sizeof($this->_requires) == 0) {
				return false;
			}

			$requireRelation = "IS_REQUIRED_BY";

			$path 		= $this->getPathFromRepo($this->_path, $this->_repoName);
			$queryBegin = "MATCH (nr:File {path: '".$path."'}) " ;
			$queryEnd	= "";

			$rCounter = 0;
			foreach ($this->_requires as $require) {
				$requirePath = $this->getPathFromRepo($require, $this->_repoName);
				$rCounter ++;
				$queryBegin .= "MATCH (nr".$rCounter.":File {path: '$requirePath'}) ";
				$queryEnd .= "CREATE (nr".$rCounter.")-[rr".$rCounter.":".$requireRelation
						."]->(nr) ";
			}

			$query = $queryBegin.$queryEnd;
			return $query;
		}






		/*******************************************************************************
		********************************************************************************
		********************************** ACCESSORS ***********************************
		********************************************************************************
		*******************************************************************************/






		/**
			Delete the part of the part before the repo name
			@return is a String
		*/
		private function getPathFromRepo($fullPath, $repoName) {
			return $repoName.'/'.explode('/'.$repoName.'/', $fullPath)[1];
		}


		/**
			Accessor for private attributes
			@return are Strings
		*/
		public function getRepoName() {
			return $this->_repoName;
		}
		public function getName() {
			return $this->_name;
		}
		public function getPath() {
			return $this->_path;
		}



		/**
			Accessor for private attributes
			@return are Arrays
		*/
		public function getFeatures() {
			return $this->_features;
		}
		public function getIncludes() {
			return $this->_includes;
		}
		public function getRequires() {
			return $this->_requires;
		}
		public function getNamespaces() {
			return $this->_namespaces;
		}
		public function getUses() {
			return $this->_uses;
		}







		/**
			toString descriptive method
			@return is a String
		*/
		public function toString() {
			return "name 			=> ".$this->_name
			  ."<br>path 			=> ".$this->_path
			  ."<br>size 			=> ".$this->_size
			  ."<br>lastModified 	=> ".$this->_lastModified
			  ."<br>extension 		=> ".$this->_extension
			  ."<br><br>";
		}

	}
?>