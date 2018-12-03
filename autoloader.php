<?php
	function loadObject($className) {
		if (strpos($className, '\\')) {
			return;
		}
		require 'objects/'.$className.'.php';
	}

	function loadException($className) {
		if (strpos($className, '\\')) {
			return;
		}
		require 'exceptions/'.$className.'.php';
	}


	spl_autoload_register('loadObject');
	spl_autoload_register('loadException');


?>