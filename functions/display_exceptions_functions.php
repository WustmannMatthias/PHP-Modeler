<?php
	/**
		Function to display properly an Exception thrown while class Node analysed a file.
		@param e is a instance or child of class Exception
		@param filePath is the file that was handled by the class Node
		@return is a String
	*/
	function printAnalysisExceptionMessage($e, $filePath) {
		$output = $filePath." analysis has thrown : <br>";
		$class 	= get_class($e);
		$output.= "<b>$class</b> found in ".$e->getFile()." line ".$e->getLine()."<br>";
		$output.= "==> ".$e->getMessage()."<br><br>";
		echo $output;
	}


	/**
		Function to display properly an Exception thrown while class Node generated Cypher
			queries for a file.
		@param e is a instance or child of class Exception
		@param filePath is the file that was handled by the class Node
		@return is a String
	*/
	function printQueriesGenerationExceptionMessage($e, $filePath) {
		$output = $filePath." queries generation has thrown : <br>";
		$class 	= get_class($e);
		$output.= "<b>$class</b> found in ".$e->getFile()." line ".$e->getLine()."<br>";
		$output.= "==> ".$e->getMessage()."<br><br>";
		echo $output;
	}


	
?>