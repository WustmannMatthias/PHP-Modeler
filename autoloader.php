<?php
	function loadObject($className) {
		@include 'objects/'.$className.'.php';
	}

	function loadException($className) {
		@include 'exceptions/'.$className.'.php';
	}


	spl_autoload_register('loadObject');
	spl_autoload_register('loadException');


?>