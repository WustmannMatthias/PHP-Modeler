<?php
	
	/*
		§§ File scan
		§§ Upload queries generation
	*/

	require_once "functions/common_functions.php";
	require_once "constants.php";
	




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
					$line = $this->replaceVariables($line, $lineCount);
				}
				if ($this->isMagicConstantInLine($line)) {
					$line = $this->replaceMagicConstant($line);
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
					$line = $this->replaceVariables($line, $lineCount);
				}
				if ($this->isMagicConstantInLine($line)) {
					$line = $this->replaceMagicConstant($line);
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
		private function replaceVariables($line, $lineCount) {
			$variableNames = $this->identifyVariable($line);
			$variableDatas = $this->findVariableValue($variableNames, $lineCount);
			$line = $this->replaceVariableWithValue($line, $variableDatas);
			return $line;
		}
		
		/**
			This method allows to detect variable names in a line. The variable names
			(with the $) will be returned in an array.
			@param line (string) is the line
			@return is the array containing the founded variable names
		*/
		private function identifyVariable($line) {
			$output = array();
			$endVariableChar = array('.', ';', ' ');
			$tab = str_split($line);
			$inVariable = false;
			$variableName = "";
			foreach ($tab as $index => $character) {
				if ($character === "$") {
					$inVariable = true;
				}
				if ($inVariable && in_array($character, $endVariableChar)) {
					array_push($output, $variableName);
					$inVariable = false;
					$variableName = "";
				}
				if ($inVariable) {
					$variableName.= $character;
				}
			}
			//displayArray($output);
			return $output;
		}

		/**
			Find where given variables are declared in file, and return their values
			@param variableNames (string) an array containing the name of the variables
			@param maxLine (int) is the line where the variable are used in the include 
				statement (the declaration is necessarily before)
			@return (string) is the value of the variable
		*/
		private function findVariableValue($variableNames, $maxLine) {
			//First, go through file and find the line where the variable is declared
			$declarationLines = array();
			$lineCount = 0;
			$fileHandler = fopen($this->_path, 'r');
			while (!feof($fileHandler)) {
				$line = fgets($fileHandler);
				$lineCount ++;
				foreach ($variableNames as $variableName) {
					if (startsWith(trim($line), $variableName)) {
						$declarationLines[$variableName] = $line;
						//echo "found<br>";
					}
				}
				if (sizeof($variableNames) == sizeof($declarationLines)) {
					break;
				}
				if ($lineCount >= $maxLine) {
					throw new Exception("Declaration of variable $variableName not found in ".$this->getPathFromRepo($this->_path, $this->_repoName).".<br>");
				}
			}

			//displayArray($declarationLines);

			//Then, analyse line et get value
			$output = array();
			foreach ($declarationLines as $variableName => $line) {
				if (strpos($line, '"')) {
					$output[$variableName] = explode('"', $line)[1];
				}
				else if (strpos($line, "'")) {
					$output[$variableName] = explode('"', $line)[1];
				}
				else {
					echo "Ununderstood variable declaration in ".$this->_path." , line ".$lineCount.".<br>";
				}
			}
			//displayArray($output);
			return $output;
		}

		/**
			Replace a variable with her value in an include or require line, and put 
			double quotes around it.
			@param line (string) is the include line
			@param variableDatas is an array associating each variable name with her value
			@return (string) is the modified line
		*/
		private function replaceVariableWithValue($line, $variableDatas) {
			foreach ($variableDatas as $variableName => $variableValue) {
				$line = str_replace($variableName, '"'.trim($variableValue).'"', $line);
			}
			return $line;
		}


		/**
			Replace the magic constant __DIR__ with the corresponding path
			@param line is the line with the magic constant in it (String)
			@return is also a String
		*/
		private function replaceMagicConstant($line) {
			//echo $line;
			$dirPath = str_replace($this->_name, "", $this->_path);
			$newline = str_replace("__DIR__", '"'.$dirPath.'"', $line);
			//echo "<br>".$newline."<br>";
			return $newline;
		}


		/**
			Help function : removes everything that is not a part of the path to a file
			@param line (string) is the line of the file where a file is included
			@return (string) is the new line
		*/
		private function removeUnnecessary($line) {
			//echo $line."<br>";
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
			//echo $output."<br>";
			return $output;
		}


		/**
			Removes eventual '//' in path
			@param line is a String
			@return is a String
		*/
		private function removeDoubleSlash($line) {
			$line = str_replace('//', '/', $line);
			//echo $line."<br>";
			return $line;
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
			Takes a relative path, or a path containing some '.' or '..' in it,
			and returns the full filled path.
			@param $path is a String
			@param $newPath is a String
		*/
		private function fillPath($path) {
			//while (strpos('../', $path) === 0) {
			//	
			//}
			$tab = explode('/', $path);
			$newTab = array();
			foreach ($tab as $item) {
				if ($item == '.') {
					continue;
				}
				elseif ($item == '..') {
					$newTab = array_trim_end($newTab);
				}
				else {
					array_push($newTab, $item);
				}
			}

			$newPath = implode('/', $newTab);
			return $newPath;
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

			//echo $query."<br>";
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