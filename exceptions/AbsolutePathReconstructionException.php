<?php 
	class AbsolutePathReconstructionException extends Exception {
		
		public function __construct($file, $dependency, $line) {
			$message = "Couldn't reconstrut absolute path of dependency $dependency
				included in $file line $line";
			parent::__construct($message);
		}

	}
?>