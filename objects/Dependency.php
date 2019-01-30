<?php 
	/*
		§§ Include dependency storage
		§§ Require dependency storage
	*/

	require_once __DIR__."/../functions/common_functions.php";

	class Dependency {

		/**
			This class describes a file inclusion through include or require
		*/

		private $_path;
		private $_type;
		private $_once;
		
		/**
			default constructor for object Dependency
			@param path : String
			@param type : String (either include or require)
			@param once : bool
		*/
		public function __construct($path, $type, $once, $parent, $line) {
			$this->_path = $path;
			$this->_once = $once;

			if (in_array($type, array('include', 'require'))) {
				$this->_type = $type;
			}
			else {
				throw new WrongDependencyTypeException($parent, $line, $type);
			}
		}


		public function getRelation() {
			if ($this->_type == 'include') {
				return "IS_INCLUDED_IN";
			}
			else {
				return "IS_REQUIRED_IN";
			}
		}

		public function getPath() {
			return $this->_path;
		}
		public function getType() {
			return $this->_type;
		}
		public function getOnce() {
			return $this->_once;
		}
	}

?>