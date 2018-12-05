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

		private $_timestamp;


		private function __construct () {
			//Instanciation through factory
		}


		public static function buildDateFromCalendar($year, $month, $day, $hour=0, 
									$minute=0, $second=0) {
			$date = new Date();

			$date->_year 	= $year;
			$date->_month	= $month;
			$date->_day 	= $day;

			$date->_hour	= $hour;
			$date->_minute	= $minute;
			$date->_second	= $second;

			$date->_timestamp = $date->computeTimestamp($year, $month, $day, $hour,
												$minute, $second);
			return $date;
		}
		
		
		public static function buildDateFromTimestamp($timestamp) {
			$date = new Date();

			$date->_timestamp = $timestamp;

			$tab = explode('.', date('Y.m.d.H.i.s', $timestamp));

			$date->_year 	= $tab[0];
			$date->_month 	= $tab[1];
			$date->_day 	= $tab[2];
			$date->_hour 	= $tab[3];
			$date->_minute 	= $tab[4];
			$date->_second 	= $tab[5];

			return $date;
		}






		private function computeTimestamp($year, $month, $day, $hour=0, 
											$minute=0, $second=0) {
			$separator = '-';
			$str = $year.$separator.$month.$separator.$day;
			$timestamp = strtotime($str);
			
			$timestamp += $second;
			$timestamp += $minute * 60;
			$timestamp += $hour * 60 * 60;

			return $timestamp;
		}







		/**
			For following methods, parameters are objects of type Date
		*/
		public function isBefore(Date $date) {
			return $this->_timestamp <= $date->getTimestamp();
		}

		public function isAfter(Date $date) {
			return $this->_timestamp > $date->getTimestamp();
		}

		public function isBetween(Date $dateBegin, Date $dateEnd) {
			return $this->isBefore($dateEnd) && $this->isAfter($dateBegin);
		}



		public function getTimestamp() {
			return $this->_timestamp;
		}


		public function toString() {
			return $this->_year.'/'.$this->_month.'/'.$this->_day.', '
					.$this->_hour.':'.$this->_minute.':'.$this->_second
					.' = '.$this->_timestamp;
		}


	}


?>