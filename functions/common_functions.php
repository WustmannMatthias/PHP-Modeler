<?php

	/**
		Display an given array
		@param tab is an array
	*/
	function displayArray($tab) {
		foreach ($tab as $key => $value) {
			echo "$key => $value <br>";
		}
		echo "<br>";
	}


	/**
		Remove the last item of an array and returns the array
	*/
	function array_trim_end($array) {
		$num = count($array);
		$num = $num-1;
		unset($array[$num]);
		return $array;
	}


	/**
		Help function to check whether a string is starting with given substring or not
		@param haystack and needle are strings
		@return is a bool
	*/
	function startsWith($haystack, $needle) {
	     $length = strlen($needle);
	     return (substr($haystack, 0, $length) === $needle);
	}


	/**
		Help function to check whether a string is ending with given substring or not
		@param haystack and needle are strings
		@return is a bool
	*/
	function endsWith($haystack, $needle) {
	    $length = strlen($needle);
	    if ($length == 0) {
	        return true;
	    }
	    return (substr($haystack, -$length) === $needle);
	}


	/**
		Just display properly an Exception
		@param e is a instance or child of class Exception
		@return is a String
	*/
		function printException($e) {
			$output = "<b>Exception found in ".$e->getFile()." line ".$e->getLine()." : ";
			$output.= $e->getMessage()."<br>";
			return $output;
		}


?>
