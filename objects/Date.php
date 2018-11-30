<?php

	/**
	 * 
	 */
	class Date {
		
		private $_year;
		private $_month;
		private $_day;
		
		private $_hour;
		private $_minute;
		private $_second;


		public function __construct($year, $month, $day, $hour=0, $minute=0, $second=0) {
			$this->_year 	= $year;
			$this->_month	= $month;
			$this->_day 	= $day;

			$this->_hour	= $hour;
			$this->_minute	= $minute;
			$this->_second	= $second;

			$this->_timestamp = $this->computeTimestamp();
		}


		private function computeTimestamp() {
			$separator = '-';
			$str = $this->_year.$separator.$this->_month.$separator.$this->_day;
			$timestamp = strtotime($str);
			
			$timestamp += $this->_second;
			$timestamp += $this->_minute * 60;
			$timestamp += $this->_hour * 60 * 60;

			return $timestamp;
		}







		/**
			For following methods, parameters are objects of type Date
		*/
		public function isBefore($date) {
			return $this->_timestamp <= $date->_timestamp;
		}

		public function isAfter($date) {
			return $this->_timestamp > $date->_timestamp;
		}

		public function isBetween($dateBegin, $dateEnd) {
			return $this->isBefore($dateEnd) && $this->isAfter($dateBegin);
		}



		public function getTimestamp() {
			return $this->_timestamp;
		}




	}


?>